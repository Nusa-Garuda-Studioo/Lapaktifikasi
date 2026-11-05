<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Toko;
use App\Models\CustomerModel;
use App\Models\SellerBadge;
use App\Models\MutasiSaldo;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\Laporan;
use App\Models\Voucher;
use App\Models\SettingKomisi;
use App\Models\SettingWebsite;
use App\Models\MitraIndustri;
use App\Models\Testimoni;
use App\Models\News;
use App\Enums\PembelianStatus;
use App\Jobs\SendAccountInvoiceWhatsapp;
use App\Services\Gateways\PaymentGatewayFactory;
use App\Services\PaymentProcessingService;
use App\Services\NewsService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AdminController extends ApiController
{
    // ==========================================
    // 1. DASHBOARD ANALYTICS
    // ==========================================

    public function getDashboard()
    {
        $totalToko = Toko::count();
        $totalCustomer = User::where('role_id', 2)->count();
        $totalSeller = User::where('role_id', 3)->count();
        $totalOrders = Pembelian::count();
        $totalRevenue = Pembayaran::sum('total');
        $totalLaporan = Laporan::count();
        $laporanPending = Laporan::where('status', 'pending')->count();
        $totalVoucherAdmin = Voucher::where('scope', 'semua_toko')->count();

        $settingKomisi = SettingKomisi::first();
        $isMaintenance = (bool) ($settingKomisi?->is_maintenance ?? false);

        return $this->sendResponse([
            'total_toko' => $totalToko,
            'total_customer' => $totalCustomer,
            'total_seller' => $totalSeller,
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'total_laporan' => $totalLaporan,
            'laporan_pending' => $laporanPending,
            'total_voucher_admin' => $totalVoucherAdmin,
            'is_maintenance' => $isMaintenance,
        ], 'Dashboard admin berhasil diambil');
    }

    // ==========================================
    // 2. KELOLA SELLER & BADGES
    // ==========================================

    public function getSellers(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $search = $request->input('search');

        $query = Toko::with(['user', 'badges'])->orderBy('id_toko', 'desc');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_toko', 'like', '%' . $search . '%')
                  ->orWhere('no_telp', 'like', '%' . $search . '%')
                  ->orWhere('akun_telegram', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('email', 'like', '%' . $search . '%');
                  });
            });
        }

        $sellers = $query->paginate($perPage);
        $allBadges = SellerBadge::all();

        return $this->sendResponse([
            'sellers' => $sellers,
            'all_badges' => $allBadges
        ], 'Data seller berhasil diambil');
    }

    public function storeSeller(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:10',
            'nama_toko' => 'required|string|max:150',
            'no_telp' => 'required|string|max:20',
            'akun_telegram' => 'required|string|max:100',
            'informasi_toko' => 'nullable|string',
            'komisi_override' => 'nullable|numeric|between:0,100',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $toko = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'profile_picture' => 'avatar-1.png',
                'role_id' => 3,
                'must_change_password' => true,
            ]);

            $namaToko = $request->nama_toko;
            $slug = Toko::generateUniqueSlug($namaToko);

            return Toko::create([
                'user_id' => $user->id,
                'nama_toko' => $namaToko,
                'slug' => $slug,
                'no_telp' => $request->no_telp,
                'akun_telegram' => $request->akun_telegram,
                'informasi_toko' => $request->informasi_toko,
                'logo_toko' => null,
                'komisi_override' => $request->komisi_override,
                'saldo' => 0,
                'status' => 'aktif',
            ]);
        });

        return $this->sendResponse($toko->load('user'), 'Seller dan toko baru berhasil dibuat', 201);
    }

    public function updateSeller(Request $request, $id_toko)
    {
        $toko = Toko::findOrFail($id_toko);

        $validator = Validator::make($request->all(), [
            'nama_toko' => 'required|string|max:150',
            'no_telp' => 'required|string|max:20',
            'akun_telegram' => 'required|string|max:100',
            'informasi_toko' => 'nullable|string',
            'komisi_override' => 'nullable|numeric|between:0,100',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $namaToko = $request->nama_toko;
        $slug = $toko->slug ?: Toko::generateUniqueSlug($namaToko, $toko->id_toko);

        $toko->update([
            'nama_toko' => $namaToko,
            'slug' => $slug,
            'no_telp' => $request->no_telp,
            'akun_telegram' => $request->akun_telegram,
            'informasi_toko' => $request->informasi_toko,
            'komisi_override' => $request->komisi_override,
        ]);

        return $this->sendResponse($toko, 'Informasi toko berhasil diperbarui');
    }

    public function toggleSellerStatus($id_toko)
    {
        $toko = Toko::findOrFail($id_toko);
        $toko->status = ($toko->status === 'aktif') ? 'nonaktif' : 'aktif';
        $toko->save();

        return $this->sendResponse($toko, 'Status toko berhasil diubah');
    }

    public function banSeller(Request $request, $id_toko)
    {
        $validator = Validator::make($request->all(), [
            'banned_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $toko = Toko::findOrFail($id_toko);
        $toko->update([
            'is_banned' => true,
            'banned_reason' => $request->banned_reason,
            'status' => 'nonaktif',
        ]);

        if ($toko->user) {
            $toko->user->update([
                'is_banned' => true,
                'banned_reason' => $request->banned_reason,
            ]);
            $toko->user->tokens()->delete();
        }

        return $this->sendResponse($toko, 'Toko dan Seller berhasil dibanned');
    }

    public function unbanSeller($id_toko)
    {
        $toko = Toko::findOrFail($id_toko);
        $toko->update([
            'is_banned' => false,
            'banned_reason' => null,
            'status' => 'aktif',
        ]);

        if ($toko->user) {
            $toko->user->update([
                'is_banned' => false,
                'banned_reason' => null,
            ]);
        }

        return $this->sendResponse($toko, 'Toko dan Seller berhasil di-unban');
    }

    public function attachBadge(Request $request, $id_toko)
    {
        $validator = Validator::make($request->all(), [
            'id_badge' => 'required|exists:tbl_seller_badge,id_badge',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $toko = Toko::findOrFail($id_toko);
        if (!$toko->badges()->where('tbl_toko_badge.id_badge', $request->id_badge)->exists()) {
            $toko->badges()->attach($request->id_badge, ['diperoleh_pada' => now()]);
        }

        return $this->sendResponse($toko->load('badges'), 'Badge berhasil ditambahkan ke toko');
    }

    public function detachBadge(Request $request, $id_toko, $id_badge)
    {
        $toko = Toko::findOrFail($id_toko);
        $toko->badges()->detach($id_badge);

        return $this->sendResponse($toko->load('badges'), 'Badge berhasil dihapus dari toko');
    }

    public function createCustomBadge(Request $request, $id_toko)
    {
        $validator = Validator::make($request->all(), [
            'nama_badge' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $toko = Toko::findOrFail($id_toko);

        $badge = SellerBadge::create([
            'nama_badge' => $request->nama_badge,
            'deskripsi' => $request->deskripsi ?? 'Badge khusus dari Admin',
            'kriteria_tipe' => 'custom_admin',
            'kriteria_nilai' => 0,
        ]);

        $toko->badges()->attach($badge->id_badge, ['diperoleh_pada' => now()]);

        return $this->sendResponse($toko->load('badges'), "Badge custom '{$badge->nama_badge}' berhasil dibuat dan diberikan ke toko", 201);
    }

    // ==========================================
    // 3. KELOLA CUSTOMER
    // ==========================================

    public function getCustomers(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $search = $request->input('search');

        $query = User::where('role_id', 2)->with(['customer.tier']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $customers = $query->paginate($perPage);

        return $this->sendResponse($customers, 'Data customer berhasil diambil');
    }

    public function banCustomer(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'banned_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $user = User::where('role_id', 2)->where('id', $id)->firstOrFail();
        $user->update([
            'is_banned' => true,
            'banned_reason' => $request->banned_reason,
        ]);
        $user->tokens()->delete();

        return $this->sendResponse($user, 'Customer berhasil dibanned');
    }

    public function unbanCustomer($id)
    {
        $user = User::where('role_id', 2)->where('id', $id)->firstOrFail();
        $user->update([
            'is_banned' => false,
            'banned_reason' => null,
        ]);

        return $this->sendResponse($user, 'Customer berhasil di-unban');
    }

    // ==========================================
    // 4. KELOLA SALDO TOKO & WITHDRAW
    // ==========================================

    public function getSaldoToko(Request $request)
    {
        $shops = Toko::with('user:id,name,email')->orderBy('saldo', 'desc')->get();
        return $this->sendResponse($shops, 'Daftar saldo toko berhasil diambil');
    }

    public function detailSaldoToko($id_toko)
    {
        $toko = Toko::with('user:id,name,email')->findOrFail($id_toko);
        $mutasi = MutasiSaldo::with('dibuatOleh:id,name')
            ->where('id_toko', $id_toko)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->sendResponse([
            'toko' => $toko,
            'mutasi' => $mutasi
        ], 'Detail saldo toko dan mutasi berhasil diambil');
    }

    public function withdrawSaldoToko(Request $request, $id_toko)
    {
        $toko = Toko::findOrFail($id_toko);

        $validator = Validator::make($request->all(), [
            'nominal' => 'required|integer|min:1',
            'keterangan' => 'required|string|max:255',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $nominal = (int) $request->nominal;

        try {
            $mutasi = DB::transaction(function () use ($id_toko, $nominal, $request) {
                $tokoLocked = Toko::where('id_toko', $id_toko)->lockForUpdate()->firstOrFail();

                if ($nominal > (float) $tokoLocked->saldo) {
                    throw new \Exception('Nominal penarikan melebihi saldo toko saat ini.');
                }

                $saldo_akhir = $tokoLocked->saldo - $nominal;

                $record = MutasiSaldo::create([
                    'id_toko' => $tokoLocked->id_toko,
                    'tipe' => 'potong_withdraw',
                    'nominal' => -$nominal,
                    'saldo_akhir' => $saldo_akhir,
                    'keterangan' => $request->keterangan,
                    'dibuat_oleh' => Auth::id(),
                ]);

                $tokoLocked->update(['saldo' => $saldo_akhir]);

                return $record;
            });
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 400);
        }

        return $this->sendResponse([
            'mutasi' => $mutasi,
            'sisa_saldo' => (float) $toko->fresh()->saldo,
        ], 'Withdraw manual berhasil diproses');
    }

    // ==========================================
    // 5. ORDERS & OPERATIONS
    // ==========================================

    public function getOrders(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $status = $request->input('status');
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Pembelian::with(['customer.user:id,name,email', 'varianLayanan.tipeLayanan.produk.toko', 'pembayaran']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', '%' . $search . '%')
                  ->orWhereHas('customer.user', function($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('email', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($startDate && $endDate) {
            try {
                $start = \Illuminate\Support\Carbon::parse($startDate)->startOfDay();
                $end = \Illuminate\Support\Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            } catch (\Exception $e) {
                // ignore invalid date
            }
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->sendResponse($orders, 'Daftar transaksi berhasil diambil');
    }

    public function retryWa(Request $request, $id_pembayaran)
    {
        $pembayaran = Pembayaran::with('pembelian')->findOrFail($id_pembayaran);
        $pembelian = $pembayaran->pembelian;

        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return $this->sendError('Notifikasi WhatsApp hanya dapat dikirim untuk transaksi yang berstatus SUCCESS.', [], 400);
        }

        if ($pembayaran->wa_last_retry_at && $pembayaran->wa_last_retry_at->diffInSeconds(now()) < 60) {
            $secondsLeft = 60 - $pembayaran->wa_last_retry_at->diffInSeconds(now());
            return $this->sendError("Mohon tunggu {$secondsLeft} detik lagi sebelum mencoba mengirim ulang.", [], 429);
        }

        $pembayaran->wa_retry_count = $pembayaran->wa_retry_count + 1;
        $pembayaran->wa_last_retry_at = now();
        $pembayaran->wa_last_retry_by = Auth::id();
        $pembayaran->save();

        SendAccountInvoiceWhatsapp::dispatch($pembelian->id_pembelian);

        \App\Models\PembelianLog::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'status_lama' => $pembelian->status->value ?? 'success',
            'status_baru' => $pembelian->status->value ?? 'success',
            'sumber_perubahan' => 'manual_admin',
            'keterangan' => 'Retry pengiriman WA ke-' . $pembayaran->wa_retry_count,
        ]);

        return $this->sendResponse([
            'wa_retry_count' => $pembayaran->wa_retry_count,
            'wa_last_retry_at' => $pembayaran->wa_last_retry_at->toDateTimeString(),
        ], 'Job pengiriman notifikasi WhatsApp berhasil didispatch!');
    }

    public function checkPaymentStatus(Request $request, $order_id)
    {
        $pembelian = Pembelian::where('order_id', $order_id)->firstOrFail();
        $gatewayName = $pembelian->payment_gateway ?? 'midtrans';

        try {
            $gateway = PaymentGatewayFactory::make($gatewayName);
            $statusData = $gateway->verifyStatus($order_id, (int) $pembelian->harga_saat_beli);

            $paymentProcessor = app(PaymentProcessingService::class);

            if ($statusData['status'] === PembelianStatus::SUCCESS) {
                $paymentProcessor->markAsSuccess($pembelian, [
                    'payment_type' => $statusData['payment_type'] ?? $gatewayName,
                    'payment_gateway' => $gatewayName,
                    'gross_amount' => $statusData['gross_amount'] ?? $pembelian->harga_saat_beli,
                    'transaction_id' => $statusData['transaction_id'] ?? null,
                ]);
            } elseif (in_array($statusData['status'], [PembelianStatus::EXPIRED, PembelianStatus::FAILED], true)) {
                $paymentProcessor->markAsFailed($pembelian, $statusData['status']->value ?? 'failed', $gatewayName);
            }

            $pembelian->refresh();

            return $this->sendResponse([
                'gateway' => $gatewayName,
                'status' => $pembelian->status->value ?? $pembelian->status,
                'raw_status' => $statusData['raw_status'] ?? null,
                'data' => $statusData,
            ], 'Status pembayaran berhasil disinkronisasi dengan server gateway');
        } catch (\Exception $e) {
            return $this->sendError('Gagal memverifikasi status dengan gateway: ' . $e->getMessage(), [], 500);
        }
    }

    // ==========================================
    // 6. SETTINGS (KOMISI & WEBSITE)
    // ==========================================

    public function getSettingKomisi()
    {
        $setting = SettingKomisi::first();
        if (!$setting) {
            $setting = SettingKomisi::create(['komisi_default' => 10.00, 'digital_file_limit_mb' => 250]);
        }
        return $this->sendResponse($setting, 'Setting komisi berhasil diambil');
    }

    public function updateSettingKomisi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'komisi_default' => 'required|numeric|between:0,100',
            'digital_file_limit_mb' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $setting = SettingKomisi::first();
        if (!$setting) {
            $setting = new SettingKomisi();
        }
        $setting->komisi_default = $request->komisi_default;
        $setting->digital_file_limit_mb = $request->digital_file_limit_mb;
        $setting->save();

        Cache::forget('setting_komisi_global');
        Cache::forget('is_maintenance_flag');

        return $this->sendResponse($setting, 'Setting komisi berhasil diperbarui');
    }

    public function toggleMaintenance(Request $request)
    {
        $setting = SettingKomisi::first();
        if (!$setting) {
            $setting = new SettingKomisi();
        }

        if ($request->has('is_maintenance')) {
            $setting->is_maintenance = (bool) $request->is_maintenance;
        } else {
            $setting->is_maintenance = !$setting->is_maintenance;
        }

        $setting->save();

        Cache::forget('setting_komisi_global');
        Cache::forget('is_maintenance_flag');

        $statusText = $setting->is_maintenance ? 'diaktifkan' : 'dinonaktifkan';
        return $this->sendResponse($setting, "Mode Maintenance platform berhasil {$statusText}");
    }

    public function getSettingWebsite()
    {
        $settings = SettingWebsite::firstOrCreate(
            ['id' => 1],
            [
                'site_name' => 'Lapaktifikasi',
                'site_description' => 'Platform Jasa Digital',
                'is_midtrans_active' => true,
                'is_tripay_active' => true,
                'is_pakasir_active' => true,
            ]
        );

        return $this->sendResponse($settings, 'Setting website berhasil diambil');
    }

    public function updateSettingWebsite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'maps_embed_url' => 'nullable|string',
            'is_midtrans_active' => 'nullable|boolean',
            'is_tripay_active' => 'nullable|boolean',
            'is_pakasir_active' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,ico,svg|max:1024',
            'auth_hero' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:3072',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $settings = SettingWebsite::firstOrCreate(['id' => 1]);

        $settings->site_name = $request->site_name;
        $settings->site_description = $request->site_description;
        $settings->contact_email = $request->contact_email;
        $settings->contact_phone = $request->contact_phone;
        $settings->address = $request->address;

        if ($request->has('maps_embed_url')) {
            $mapsEmbed = $request->maps_embed_url;
            if (!empty($mapsEmbed) && preg_match('/src=[\'"]([^\'"]+)[\'"]/', $mapsEmbed, $matches)) {
                $mapsEmbed = $matches[1];
            }
            $settings->maps_embed_url = $mapsEmbed;
        }

        if ($request->has('is_midtrans_active')) $settings->is_midtrans_active = $request->boolean('is_midtrans_active');
        if ($request->has('is_tripay_active')) $settings->is_tripay_active = $request->boolean('is_tripay_active');
        if ($request->has('is_pakasir_active')) $settings->is_pakasir_active = $request->boolean('is_pakasir_active');

        if ($request->hasFile('logo')) {
            if ($settings->logo_path && File::exists(public_path($settings->logo_path))) {
                @File::delete(public_path($settings->logo_path));
            }
            $logo = $request->file('logo');
            $logoName = 'logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('assets/img'), $logoName);
            $settings->logo_path = 'assets/img/' . $logoName;
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path && File::exists(public_path($settings->favicon_path))) {
                @File::delete(public_path($settings->favicon_path));
            }
            $favicon = $request->file('favicon');
            $faviconName = 'favicon_' . time() . '.' . $favicon->getClientOriginalExtension();
            $favicon->move(public_path('assets/img'), $faviconName);
            $settings->favicon_path = 'assets/img/' . $faviconName;
        }

        if ($request->hasFile('auth_hero')) {
            if ($settings->auth_hero_path && File::exists(public_path($settings->auth_hero_path))) {
                @File::delete(public_path($settings->auth_hero_path));
            }
            $authHero = $request->file('auth_hero');
            $authHeroName = 'auth_hero_' . time() . '.' . $authHero->getClientOriginalExtension();
            $authHero->move(public_path('assets/img'), $authHeroName);
            $settings->auth_hero_path = 'assets/img/' . $authHeroName;
        }

        $settings->save();

        Cache::forget('setting_website_global');
        Cache::forget('api_landing_home');
        Cache::forget('api_payment_channels');

        return $this->sendResponse($settings, 'Setting website berhasil diperbarui');
    }

    // ==========================================
    // 7. KELOLA LAPORAN
    // ==========================================

    public function getLaporan(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $status = $request->input('status');

        $query = Laporan::with('user:id,name,email')->orderBy('created_at', 'desc');
        if ($status) {
            $query->where('status', $status);
        }

        $laporan = $query->paginate($perPage);

        return $this->sendResponse($laporan, 'Data laporan berhasil diambil');
    }

    public function updateLaporanStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,proses,selesai'
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $laporan = Laporan::findOrFail($id);
        $oldStatus = $laporan->status;
        $laporan->update(['status' => $request->status]);

        if ($oldStatus !== $request->status) {
            if ($request->status === 'selesai') {
                app(\App\Services\Telegram\NotificationRouter::class)->notifyTicketCompleted($laporan);
            } else {
                app(\App\Services\Telegram\NotificationRouter::class)->notifyTicketStatusUpdated($laporan, $oldStatus, $request->status);
            }
        }

        return $this->sendResponse($laporan, 'Status laporan berhasil diubah');
    }

    // ==========================================
    // 8. VOUCHER ADMIN (GLOBAL)
    // ==========================================

    public function getVoucherAdmin(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $vouchers = Voucher::where('scope', 'semua_toko')->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->sendResponse($vouchers, 'Data voucher admin berhasil diambil');
    }

    public function storeVoucherAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|max:50|unique:tbl_voucher,kode',
            'nama_voucher' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tipe_diskon' => 'required|in:persen,nominal,persentase',
            'nilai_diskon' => 'required|numeric|min:0',
            'maksimal_potongan' => 'nullable|numeric|min:0',
            'minimal_transaksi' => 'required|numeric|min:0',
            'kuota_total' => 'nullable|integer|min:1',
            'berlaku_dari' => 'nullable|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $data = $request->all();
        $data['scope'] = 'semua_toko';
        $data['id_toko'] = null;
        $data['kode'] = strtoupper(trim($data['kode']));
        if ($data['tipe_diskon'] === 'persentase') $data['tipe_diskon'] = 'persen';
        $data['is_active'] = $request->has('is_active') ? $request->is_active : true;

        $voucher = Voucher::create($data);

        return $this->sendResponse($voucher, 'Voucher global berhasil ditambahkan', 201);
    }

    public function updateVoucherAdmin(Request $request, $id)
    {
        $voucher = Voucher::where('scope', 'semua_toko')->where('id_voucher', $id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|max:50|unique:tbl_voucher,kode,'.$id.',id_voucher',
            'nama_voucher' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tipe_diskon' => 'required|in:persen,nominal,persentase',
            'nilai_diskon' => 'required|numeric|min:0',
            'maksimal_potongan' => 'nullable|numeric|min:0',
            'minimal_transaksi' => 'required|numeric|min:0',
            'kuota_total' => 'nullable|integer|min:1',
            'berlaku_dari' => 'nullable|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $data = $request->all();
        $data['kode'] = strtoupper(trim($data['kode']));
        if (isset($data['tipe_diskon']) && $data['tipe_diskon'] === 'persentase') $data['tipe_diskon'] = 'persen';
        
        $voucher->update($data);

        return $this->sendResponse($voucher, 'Voucher global berhasil diperbarui');
    }

    public function toggleVoucherAdminStatus($id)
    {
        $voucher = Voucher::where('scope', 'semua_toko')->where('id_voucher', $id)->firstOrFail();
        $voucher->is_active = !$voucher->is_active;
        $voucher->save();

        return $this->sendResponse($voucher, 'Status voucher berhasil diubah');
    }

    public function destroyVoucherAdmin($id)
    {
        $voucher = Voucher::where('scope', 'semua_toko')->where('id_voucher', $id)->firstOrFail();
        $voucher->delete();

        return $this->sendResponse([], 'Voucher global berhasil dihapus');
    }

    // ==========================================
    // 9. MITRA INDUSTRI (CRUD)
    // ==========================================

    public function getMitraIndustri()
    {
        $mitras = MitraIndustri::orderBy('id', 'desc')->get();
        return $this->sendResponse($mitras, 'Daftar mitra industri berhasil diambil');
    }

    public function storeMitraIndustri(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $mitra = new MitraIndustri();
        $mitra->name = $request->name;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $targetDir = public_path('assets/img/mitra_industri');
            if (!file_exists($targetDir)) @mkdir($targetDir, 0755, true);
            $image->move($targetDir, $imageName);
            $mitra->image_path = 'assets/img/mitra_industri/' . $imageName;
        }

        $mitra->is_active = true;
        $mitra->save();

        return $this->sendResponse($mitra, 'Mitra industri berhasil ditambahkan', 201);
    }

    public function updateMitraIndustri(Request $request, $id)
    {
        $mitra = MitraIndustri::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $mitra->name = $request->name;

        if ($request->hasFile('image')) {
            if ($mitra->image_path && File::exists(public_path($mitra->image_path))) {
                @File::delete(public_path($mitra->image_path));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $targetDir = public_path('assets/img/mitra_industri');
            if (!file_exists($targetDir)) @mkdir($targetDir, 0755, true);
            $image->move($targetDir, $imageName);
            $mitra->image_path = 'assets/img/mitra_industri/' . $imageName;
        }

        $mitra->save();

        return $this->sendResponse($mitra, 'Mitra industri berhasil diperbarui');
    }

    public function toggleMitraIndustri($id)
    {
        $mitra = MitraIndustri::findOrFail($id);
        $mitra->is_active = !$mitra->is_active;
        $mitra->save();

        return $this->sendResponse($mitra, 'Status mitra industri berhasil diubah');
    }

    public function destroyMitraIndustri($id)
    {
        $mitra = MitraIndustri::findOrFail($id);
        if ($mitra->image_path && File::exists(public_path($mitra->image_path))) {
            @File::delete(public_path($mitra->image_path));
        }
        $mitra->delete();

        return $this->sendResponse([], 'Mitra industri berhasil dihapus');
    }

    // ==========================================
    // 10. TESTIMONI (CRUD)
    // ==========================================

    public function getTestimoni()
    {
        $testimonis = Testimoni::orderBy('id', 'desc')->get();
        return $this->sendResponse($testimonis, 'Daftar testimoni berhasil diambil');
    }

    public function storeTestimoni(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $testimoni = Testimoni::create([
            'name' => $request->name,
            'role' => $request->role,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_active' => true,
        ]);

        return $this->sendResponse($testimoni, 'Testimoni berhasil ditambahkan', 201);
    }

    public function updateTestimoni(Request $request, $id)
    {
        $testimoni = Testimoni::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $testimoni->update([
            'name' => $request->name,
            'role' => $request->role,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return $this->sendResponse($testimoni, 'Testimoni berhasil diperbarui');
    }

    public function toggleTestimoni($id)
    {
        $testimoni = Testimoni::findOrFail($id);
        $testimoni->is_active = !$testimoni->is_active;
        $testimoni->save();

        return $this->sendResponse($testimoni, 'Status testimoni berhasil diubah');
    }

    public function destroyTestimoni($id)
    {
        $testimoni = Testimoni::findOrFail($id);
        $testimoni->delete();

        return $this->sendResponse([], 'Testimoni berhasil dihapus');
    }

    // ==========================================
    // 11. NEWS / BERITA (CRUD)
    // ==========================================

    public function getNews(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = News::with('admin:id,name')->orderBy('created_at', 'desc');

        if ($status && in_array($status, ['draft', 'published'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('judul', 'like', '%' . $search . '%')
                  ->orWhere('subjudul', 'like', '%' . $search . '%');
            });
        }

        $news = $query->paginate($perPage);

        return $this->sendResponse($news, 'Daftar berita admin berhasil diambil');
    }

    public function storeNews(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'subjudul' => 'nullable|string|max:255',
            'konten' => 'required|string',
            'status' => 'required|in:draft,published',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $validated = $validator->validated();

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('news', 'public');
            $validated['gambar'] = $path;
        }

        $newsService = app(NewsService::class);
        $news = $newsService->create($validated, Auth::id());

        return $this->sendResponse($news, 'Berita berhasil dibuat', 201);
    }

    public function showNews($id)
    {
        $news = News::with('admin:id,name')->findOrFail($id);
        return $this->sendResponse($news, 'Detail berita berhasil diambil');
    }

    public function updateNews(Request $request, $id)
    {
        $news = News::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'subjudul' => 'nullable|string|max:255',
            'konten' => 'required|string',
            'status' => 'required|in:draft,published',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $validated = $validator->validated();

        if ($request->hasFile('gambar')) {
            if ($news->gambar && Storage::disk('public')->exists($news->gambar)) {
                Storage::disk('public')->delete($news->gambar);
            }

            $path = $request->file('gambar')->store('news', 'public');
            $validated['gambar'] = $path;
        }

        $newsService = app(NewsService::class);
        $updatedNews = $newsService->update($news, $validated);

        return $this->sendResponse($updatedNews, 'Berita berhasil diperbarui');
    }

    public function destroyNews($id)
    {
        $news = News::findOrFail($id);
        $newsService = app(NewsService::class);
        $newsService->delete($news);

        return $this->sendResponse([], 'Berita berhasil dihapus');
    }
}
