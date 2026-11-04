<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produk;
use App\Models\TipeLayanan;
use App\Models\VarianLayanan;
use App\Models\StokAkun;
use App\Models\Toko;
use App\Enums\StokStatus;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Pembayaran;
use App\Enums\PembelianStatus;
use App\Jobs\SendAccountInvoiceWhatsapp;

class DigitalAdminController extends Controller
{
    // === Halaman Inventaris Gabungan ===
    public function inventaris_index()
    {
        $user = Auth::user();
        if ($user->role_id == 1) {
            $produk = Produk::where('tipe_produk', 'digital')->get();
            $tipeSemua = TipeLayanan::whereHas('produk', fn($q) => $q->where('tipe_produk', 'digital'))->with('produk')->get();
            $tipeAktif = TipeLayanan::where('status', 'aktif')->whereHas('produk', fn($q) => $q->where('tipe_produk', 'digital'))->with('produk')->get();
            $varianSemua = VarianLayanan::whereHas('tipeLayanan.produk', fn($q) => $q->where('tipe_produk', 'digital'))->with('tipeLayanan.produk')->get();
            $varianAktif = VarianLayanan::where('status', 'aktif')->whereHas('tipeLayanan.produk', fn($q) => $q->where('tipe_produk', 'digital'))->with('tipeLayanan.produk')->get();
        } else {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            $produk = Produk::where('tipe_produk', 'digital')
                ->where('id_toko', $toko->id_toko)
                ->get();
            $tipeSemua = TipeLayanan::whereHas('produk', function ($q) use ($toko) {
                $q->where('id_toko', $toko->id_toko)->where('tipe_produk', 'digital');
            })->with('produk')->get();
            $tipeAktif = TipeLayanan::where('status', 'aktif')
                ->whereHas('produk', function ($q) use ($toko) {
                    $q->where('id_toko', $toko->id_toko)->where('tipe_produk', 'digital');
                })
                ->get();
            $varianSemua = VarianLayanan::whereHas('tipeLayanan.produk', function ($q) use ($toko) {
                $q->where('id_toko', $toko->id_toko)->where('tipe_produk', 'digital');
            })->with('tipeLayanan.produk')->get();
            $varianAktif = VarianLayanan::where('status', 'aktif')
                ->whereHas('tipeLayanan.produk', function ($q) use ($toko) {
                    $q->where('id_toko', $toko->id_toko)->where('tipe_produk', 'digital');
                })
                ->get();
        }

        $limit_mb = \App\Models\SettingKomisi::first()?->digital_file_limit_mb ?? 250;

        return view('digital_admin.inventaris.index', compact('produk', 'tipeSemua', 'tipeAktif', 'varianSemua', 'varianAktif', 'limit_mb'));
    }

    public function tipe_store(Request $request)
    {
        $user = Auth::user();
        $rules = [
            'id_produk' => 'required|exists:tbl_produk,id_produk',
            'nama_tipe' => 'required|max:50',
            'status' => 'required|in:aktif,nonaktif',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Scope validation
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            $prod = Produk::where('id_produk', $request->id_produk)->where('id_toko', $toko->id_toko)->first();
            if (!$prod) {
                abort(403, 'Unauthorized access.');
            }
        }

        TipeLayanan::create($request->only('id_produk', 'nama_tipe', 'status'));

        Session::flash('success', 'Berhasil menambahkan tipe layanan.');
        return redirect()->route('digital.inventaris.index', ['tab' => 'tipe']);
    }

    public function tipe_update(Request $request, $id)
    {
        $user = Auth::user();
        $tipe = TipeLayanan::findOrFail($id);

        // Scope check existing
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            if ($tipe->produk->id_toko != $toko->id_toko) {
                abort(403, 'Unauthorized access.');
            }
        }

        $rules = [
            'id_produk' => 'required|exists:tbl_produk,id_produk',
            'nama_tipe' => 'required|max:50',
            'status' => 'required|in:aktif,nonaktif',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Scope validation for new product
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            $prod = Produk::where('id_produk', $request->id_produk)->where('id_toko', $toko->id_toko)->first();
            if (!$prod) {
                abort(403, 'Unauthorized access.');
            }
        }

        $tipe->update($request->only('id_produk', 'nama_tipe', 'status'));

        Session::flash('success', 'Berhasil memperbarui tipe layanan.');
        return redirect()->route('digital.inventaris.index', ['tab' => 'tipe']);
    }

    public function tipe_destroy($id)
    {
        $user = Auth::user();
        $tipe = TipeLayanan::findOrFail($id);

        // Scope check
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            if ($tipe->produk->id_toko != $toko->id_toko) {
                abort(403, 'Unauthorized access.');
            }
        }

        try {
            $varianIds = VarianLayanan::where('id_tipe', $tipe->id_tipe)->pluck('id_varian');
            $hasPurchases = \App\Models\Pembelian::whereIn('id_varian', $varianIds)->exists();

            if ($hasPurchases) {
                VarianLayanan::where('id_tipe', $tipe->id_tipe)->update(['status' => 'nonaktif']);
                $tipe->update(['status' => 'nonaktif']);

                Session::flash('success', 'Tipe layanan digital ini memiliki riwayat transaksi. Statusnya diubah menjadi non-aktif agar data pembeli tetap aman.');
                return redirect()->route('digital.inventaris.index', ['tab' => 'tipe']);
            }

            VarianLayanan::where('id_tipe', $tipe->id_tipe)->delete();
            $tipe->delete();
            Session::flash('success', 'Berhasil menghapus tipe layanan digital.');
        } catch (\Exception $e) {
            $tipe->update(['status' => 'nonaktif']);
            Session::flash('success', 'Tipe layanan diubah menjadi non-aktif demi keamanan data riwayat transaksi.');
        }

        return redirect()->route('digital.inventaris.index', ['tab' => 'tipe']);
    }

    // === 2. CRUD Varian Layanan ===

    public function varian_store(Request $request)
    {
        $user = Auth::user();
        $limit_mb = \Illuminate\Support\Facades\DB::table('tbl_setting_komisi')->first()->digital_file_limit_mb ?? 250;
        $limit_kb = $limit_mb * 1024;

        $rules = [
            'id_tipe' => 'required|exists:tbl_tipe_layanan,id_tipe',
            'nama_varian' => 'required|max:50',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable',
            'status' => 'required|in:aktif,nonaktif',
            'file_digital' => 'required|file|mimes:zip,rar,7z,tar,gz,pdf,epub,mobi,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,mp4,wav|max:' . $limit_kb,
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Scope check
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            $tipe = TipeLayanan::where('id_tipe', $request->id_tipe)
                ->whereHas('produk', function($q) use ($toko) {
                    $q->where('id_toko', $toko->id_toko);
                })->first();
            if (!$tipe) {
                abort(403, 'Unauthorized access.');
            }
        }

        $data = $request->only('id_tipe', 'nama_varian', 'harga', 'deskripsi', 'status');
        $data['durasi_hari'] = 0; // Digital products have no duration

        if ($request->hasFile('file_digital')) {
            $file = $request->file('file_digital');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = time() . '_' . \Illuminate\Support\Str::random(32) . '.' . $extension;
            $targetDir = storage_path('app/private/file_digital');
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $fileName);
            $data['file_path'] = $fileName;
        }

        VarianLayanan::create($data);

        Session::flash('success', 'Berhasil menambahkan varian layanan digital.');
        return redirect()->route('digital.inventaris.index', ['tab' => 'varian']);
    }

    public function varian_update(Request $request, $id)
    {
        $user = Auth::user();
        $varian = VarianLayanan::findOrFail($id);

        // Scope check existing
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            if ($varian->tipeLayanan->produk->id_toko != $toko->id_toko) {
                abort(403, 'Unauthorized access.');
            }
        }

        $limit_mb = \Illuminate\Support\Facades\DB::table('tbl_setting_komisi')->first()->digital_file_limit_mb ?? 250;
        $limit_kb = $limit_mb * 1024;

        $rules = [
            'id_tipe' => 'required|exists:tbl_tipe_layanan,id_tipe',
            'nama_varian' => 'required|max:50',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable',
            'status' => 'required|in:aktif,nonaktif',
            'file_digital' => 'nullable|file|mimes:zip,rar,7z,tar,gz,pdf,epub,mobi,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,mp4,wav|max:' . $limit_kb,
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Scope check new tipe
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            $tipe = TipeLayanan::where('id_tipe', $request->id_tipe)
                ->whereHas('produk', function($q) use ($toko) {
                    $q->where('id_toko', $toko->id_toko);
                })->first();
            if (!$tipe) {
                abort(403, 'Unauthorized access.');
            }
        }

        $data = $request->only('id_tipe', 'nama_varian', 'harga', 'deskripsi', 'status');

        if ($request->hasFile('file_digital')) {
            // Delete old file from both private storage and legacy public directory
            if ($varian->file_path) {
                $oldPrivate = storage_path('app/private/file_digital/' . $varian->file_path);
                $oldPublic = public_path('assets/file_digital/' . $varian->file_path);
                if (file_exists($oldPrivate)) @unlink($oldPrivate);
                if (file_exists($oldPublic)) @unlink($oldPublic);
            }

            $file = $request->file('file_digital');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = time() . '_' . \Illuminate\Support\Str::random(32) . '.' . $extension;
            $targetDir = storage_path('app/private/file_digital');
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $fileName);
            $data['file_path'] = $fileName;
        }

        $varian->update($data);

        Session::flash('success', 'Berhasil memperbarui varian layanan digital.');
        return redirect()->route('digital.inventaris.index', ['tab' => 'varian']);
    }

    public function varian_destroy($id)
    {
        $user = Auth::user();
        $varian = VarianLayanan::findOrFail($id);

        // Scope check
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            if ($varian->tipeLayanan->produk->id_toko != $toko->id_toko) {
                abort(403, 'Unauthorized access.');
            }
        }

        try {
            $hasPurchases = \App\Models\Pembelian::where('id_varian', $varian->id_varian)->exists();

            if ($hasPurchases) {
                $varian->update(['status' => 'nonaktif']);
                Session::flash('success', 'Varian digital ini memiliki riwayat transaksi. Status varian diubah menjadi non-aktif agar data pembeli tetap aman.');
                return redirect()->route('digital.inventaris.index', ['tab' => 'varian']);
            }

            if ($varian->file_path && file_exists(public_path('assets/file_digital/' . $varian->file_path))) {
                @unlink(public_path('assets/file_digital/' . $varian->file_path));
            }

            $varian->delete();
            Session::flash('success', 'Berhasil menghapus varian layanan digital.');
        } catch (\Exception $e) {
            $varian->update(['status' => 'nonaktif']);
            Session::flash('success', 'Varian diubah menjadi non-aktif agar data transaksi tetap aman.');
        }

        return redirect()->route('digital.inventaris.index', ['tab' => 'varian']);
    }

    // === 3. CRUD Stok Akun (Single + Bulk) ===

    public function stok_store(Request $request)
    {
        $user = Auth::user();
        $rules = [
            'id_varian' => 'required|exists:tbl_varian_layanan,id_varian',
            'email_username' => 'required|max:150',
            'password' => 'required',
            'catatan' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Scope check
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            $varian = VarianLayanan::where('id_varian', $request->id_varian)
                ->whereHas('tipeLayanan.produk', function($q) use ($toko) {
                    $q->where('id_toko', $toko->id_toko);
                })->first();
            if (!$varian) {
                abort(403, 'Unauthorized access.');
            }
        }

        StokAkun::create([
            'id_varian' => $request->id_varian,
            'email_username' => $request->email_username,
            'password_encrypted' => $request->password,
            'catatan' => $request->catatan,
            'status' => StokStatus::TERSEDIA,
        ]);

        Session::flash('success', 'Berhasil menambahkan stok akun.');
        return redirect()->route('digital.inventaris.index', ['tab' => 'stok']);
    }

    public function stok_bulk_store(Request $request)
    {
        $user = Auth::user();
        $rules = [
            'id_varian' => 'required|exists:tbl_varian_layanan,id_varian',
            'bulk_data' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Scope check
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            $varian = VarianLayanan::where('id_varian', $request->id_varian)
                ->whereHas('tipeLayanan.produk', function($q) use ($toko) {
                    $q->where('id_toko', $toko->id_toko);
                })->first();
            if (!$varian) {
                abort(403, 'Unauthorized access.');
            }
        }

        $lines = explode("\n", str_replace("\r", "", $request->bulk_data));
        $count = 0;

        foreach ($lines as $line) {
            $parts = explode("|", trim($line));
            if (count($parts) >= 2) {
                StokAkun::create([
                    'id_varian' => $request->id_varian,
                    'email_username' => trim($parts[0]),
                    'password_encrypted' => trim($parts[1]),
                    'catatan' => isset($parts[2]) ? trim($parts[2]) : null,
                    'status' => StokStatus::TERSEDIA,
                ]);
                $count++;
            }
        }

        Session::flash('success', "Berhasil menambahkan {$count} stok akun secara bulk.");
        return redirect()->route('digital.inventaris.index', ['tab' => 'stok']);
    }

    public function stok_decrypt($id)
    {
        $user = Auth::user();
        $stok = StokAkun::findOrFail($id);

        // Scope check
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            if ($stok->varianLayanan->tipeLayanan->produk->id_toko != $toko->id_toko) {
                abort(403, 'Unauthorized access.');
            }
        }
        
        return response()->json([
            'email_username' => $stok->email_username,
            'password' => $stok->password_encrypted,
            'catatan' => $stok->catatan
        ]);
    }

    public function stok_destroy($id)
    {
        $user = Auth::user();
        $stok = StokAkun::findOrFail($id);

        // Scope check
        if ($user->role_id != 1) {
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            if ($stok->varianLayanan->tipeLayanan->produk->id_toko != $toko->id_toko) {
                abort(403, 'Unauthorized access.');
            }
        }

        $stok->delete();
        Session::flash('success', 'Berhasil menghapus stok akun.');
        return redirect()->route('digital.inventaris.index', ['tab' => 'stok']);
    }

    // === 4. Halaman Histori Penjualan ===
    public function histori_index(Request $request)
    {
        $user = Auth::user();
        
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $statusInput = $request->input('status');

        $startDate = null;
        $endDate = null;

        if ($startDateInput && $endDateInput) {
            try {
                $startDate = \Illuminate\Support\Carbon::parse($startDateInput)->startOfDay();
                $endDate = \Illuminate\Support\Carbon::parse($endDateInput)->endOfDay();
                
                if ($startDate->greaterThan($endDate)) {
                    return redirect()->back()->with('error', 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
                }
                
                if ($startDate->diffInDays($endDate) > 365) {
                    return redirect()->back()->with('error', 'Rentang tanggal maksimal adalah 1 tahun.');
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Format tanggal tidak valid.');
            }
        }

        if ($user->role_id == 1) {
            // Admin sees all Pembelian (orders)
            $query = \App\Models\Pembelian::with(['customer.user', 'logs', 'pembayaran', 'stokAkun.varianLayanan.tipeLayanan.produk']);

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            if ($statusInput) {
                $query->where('status', $statusInput);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();
            
            return view('premium_admin.histori.index', compact('orders', 'user'));
        } else {
            // Seller sees only their sold premium accounts
            $toko = Toko::where('user_id', $user->id)->firstOrFail();
            $query = StokAkun::whereHas('varianLayanan.tipeLayanan.produk', function($q) use ($toko) {
                $q->where('id_toko', $toko->id_toko);
            })
            ->with(['varianLayanan.tipeLayanan.produk', 'pembelian.customer.user', 'pembelian.logs', 'pembelian.pembayaran'])
            ->where('status', StokStatus::TERJUAL);

            if ($startDate && $endDate) {
                $query->whereBetween('tanggal_terjual', [$startDate, $endDate]);
            }

            $stokTerjual = $query->orderBy('tanggal_terjual', 'desc')->get();

            return view('premium_admin.histori.index', compact('stokTerjual', 'user'));
        }
    }

    // === 5. Retry Kirim Notifikasi WhatsApp Manual ===
    public function retryWa(Request $request, $id_pembayaran)
    {
        $pembayaran = Pembayaran::with('pembelian')->findOrFail($id_pembayaran);
        $pembelian = $pembayaran->pembelian;

        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi WhatsApp hanya dapat dikirim untuk transaksi yang berstatus SUCCESS.'
            ], 400);
        }

        // Rate limit 60 seconds
        if ($pembayaran->wa_last_retry_at && $pembayaran->wa_last_retry_at->diffInSeconds(now()) < 60) {
            $secondsLeft = 60 - $pembayaran->wa_last_retry_at->diffInSeconds(now());
            return response()->json([
                'success' => false,
                'message' => "Mohon tunggu {$secondsLeft} detik lagi sebelum mencoba mengirim ulang."
            ], 429);
        }

        // Update retry stats
        $pembayaran->wa_retry_count = $pembayaran->wa_retry_count + 1;
        $pembayaran->wa_last_retry_at = now();
        $pembayaran->wa_last_retry_by = Auth::id();
        $pembayaran->save();

        // Dispatch WA job
        SendAccountInvoiceWhatsapp::dispatch($pembelian->id_pembelian);

        // Audit Trail Log
        \App\Models\PembelianLog::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'status_lama' => $pembelian->status->value ?? 'success',
            'status_baru' => $pembelian->status->value ?? 'success',
            'sumber_perubahan' => 'manual_admin',
            'keterangan' => 'Retry pengiriman WA ke-' . $pembayaran->wa_retry_count,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job pengiriman notifikasi WhatsApp berhasil didispatch!',
            'wa_retry_count' => $pembayaran->wa_retry_count,
            'wa_last_retry_at' => $pembayaran->wa_last_retry_at->toDateTimeString(),
        ]);
    }
}
