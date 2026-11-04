<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\CustomerModel;
use App\Models\CustomerTier;
use App\Models\Pembelian;
use App\Models\Voucher;
use App\Models\VoucherKlaim;
use App\Models\Review;
use App\Models\Laporan;
use App\Enums\PembelianStatus;
use App\Services\PaymentProcessingService;
use App\Services\Gateways\PaymentGatewayFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CustomerPremiumController extends ApiController
{
    protected PaymentProcessingService $paymentProcessor;

    public function __construct(PaymentProcessingService $paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;
    }

    /**
     * Data keanggotaan customer (Member / Tier / Progress / Eligible Vouchers)
     */
    public function getMemberData(Request $request)
    {
        $idCustomerUser = $request->user()->id;
        $customer = CustomerModel::with('tier')->where('user_id', $idCustomerUser)->first();

        if (!$customer) {
            return $this->sendError('Profil pelanggan tidak ditemukan.', [], 404);
        }

        $progressInfo = $customer->progressKeTierBerikutnya();
        $allTiers = CustomerTier::orderBy('urutan', 'asc')->get();

        $vouchers = Voucher::with('toko')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('kuota_total')->orWhereColumn('kuota_terpakai', '<', 'kuota_total');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $claimedVoucherIds = VoucherKlaim::where('id_customer', $customer->id)
            ->pluck('id_voucher')
            ->toArray();

        return $this->sendResponse([
            'customer' => $customer,
            'progress' => $progressInfo,
            'tiers' => $allTiers,
            'vouchers' => $vouchers,
            'claimed_vouchers' => $claimedVoucherIds
        ], 'Data member berhasil diambil');
    }

    /**
     * Data referral customer
     */
    public function getReferralData(Request $request)
    {
        $idCustomerUser = $request->user()->id;
        $customer = CustomerModel::where('user_id', $idCustomerUser)->first();

        if (!$customer) {
            return $this->sendError('Profil pelanggan tidak ditemukan.', [], 404);
        }

        if (empty($customer->kode_referral)) {
            $customer->kode_referral = 'REF-' . strtoupper(Str::random(6));
            $customer->save();
        }

        $shareUrl = url('/pendaftaran?ref=' . $customer->kode_referral);
        $bonusAmount = (float) config('referral.bonus_akumulasi', 50000);

        $referredCustomers = CustomerModel::with('user:id,name,email,created_at')
            ->where('direferensikan_oleh', $customer->id)
            ->get();

        return $this->sendResponse([
            'kode_referral' => $customer->kode_referral,
            'share_url' => $shareUrl,
            'bonus_akumulasi' => $bonusAmount,
            'jumlah_referral_sukses' => $customer->jumlah_referral_sukses,
            'referred_customers' => $referredCustomers
        ], 'Data referral berhasil diambil');
    }

    /**
     * Klaim Voucher
     */
    public function klaimVoucher(Request $request, $id_voucher)
    {
        $idCustomerUser = $request->user()->id;
        $customer = CustomerModel::where('user_id', $idCustomerUser)->first();

        if (!$customer) return $this->sendError('Profil pelanggan tidak ditemukan.', [], 404);

        $voucher = Voucher::find($id_voucher);

        if (!$voucher || !$voucher->is_active) return $this->sendError('Voucher tidak aktif atau tidak ditemukan.', [], 400);

        $now = now();
        if ($voucher->berlaku_dari && $now->lt($voucher->berlaku_dari)) return $this->sendError('Voucher belum berlaku.', [], 400);
        if ($voucher->berlaku_sampai && $now->gt($voucher->berlaku_sampai)) return $this->sendError('Voucher telah kedaluwarsa.', [], 400);
        if ($voucher->kuota_total !== null && $voucher->kuota_terpakai >= $voucher->kuota_total) return $this->sendError('Kuota voucher telah habis.', [], 400);

        $alreadyClaimed = VoucherKlaim::where('id_voucher', $id_voucher)->where('id_customer', $customer->id)->exists();
        if ($alreadyClaimed) return $this->sendError('Anda sudah mengklaim voucher ini.', [], 400);

        VoucherKlaim::create([
            'id_voucher' => $id_voucher,
            'id_customer' => $customer->id,
            'id_pembelian' => null,
            'created_at' => now(),
        ]);

        return $this->sendResponse([], 'Voucher berhasil diklaim!');
    }

    /**
     * Riwayat Pembelian & Auto-Sync
     */
    public function getRiwayat(Request $request)
    {
        $idCustomerUser = $request->user()->id;
        $customer = CustomerModel::where('user_id', $idCustomerUser)->first();

        if (!$customer) return $this->sendError('Profil pelanggan tidak ditemukan.', [], 404);

        // Auto-sync pending transactions in last 24h
        $pendingPurchases = Pembelian::where('id_customer', $customer->id)
            ->where('status', PembelianStatus::PENDING)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        foreach ($pendingPurchases as $pPending) {
            $isExpiredByTime = $pPending->reserved_until && $pPending->reserved_until < now();
            try {
                $gatewayName = $pPending->payment_gateway ?? 'midtrans';
                $gateway = PaymentGatewayFactory::make($gatewayName);
                $statusData = $gateway->verifyStatus($pPending->order_id, (int)$pPending->harga_saat_beli);

                if ($statusData['status'] === PembelianStatus::SUCCESS) {
                    $this->paymentProcessor->markAsSuccess($pPending, [
                        'payment_type' => $statusData['payment_type'] ?? 'unknown',
                        'payment_gateway' => $gatewayName,
                        'gross_amount' => $statusData['gross_amount'] ?? $pPending->harga_saat_beli,
                        'transaction_id' => $statusData['transaction_id'] ?? null,
                    ]);
                } elseif (in_array($statusData['status'], [PembelianStatus::FAILED, PembelianStatus::EXPIRED]) || $isExpiredByTime) {
                    $this->paymentProcessor->markAsFailed($pPending, $statusData['status']->value ?? 'expire', $gatewayName);
                }
            } catch (\Exception $e) {
                if ($isExpiredByTime) {
                    $this->paymentProcessor->markAsFailed($pPending, 'expire', $pPending->payment_gateway ?? 'system');
                }
            }
        }

        $query = Pembelian::with(['varianLayanan.tipeLayanan.produk.toko', 'pembayaran', 'review'])
            ->where('id_customer', $customer->id);

        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');

        if ($startDateInput && $endDateInput) {
            try {
                $startDate = \Illuminate\Support\Carbon::parse($startDateInput)->startOfDay();
                $endDate = \Illuminate\Support\Carbon::parse($endDateInput)->endOfDay();
                
                if ($startDate->greaterThan($endDate)) {
                    return $this->sendError('Tanggal mulai tidak boleh lebih besar dari tanggal akhir.', [], 400);
                }
                
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } catch (\Exception $e) {
                return $this->sendError('Format tanggal tidak valid.', [], 400);
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        $pembelian = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->sendResponse($pembelian, 'Riwayat pembelian berhasil diambil');
    }

    /**
     * Kredensial Akun (Decrypt On-Demand)
     */
    public function getKredensial(Request $request, $order_id)
    {
        $pembelian = Pembelian::with('stokAkun')->where('order_id', $order_id)->first();

        if (!$pembelian) return $this->sendError('Pesanan tidak ditemukan', [], 404);

        $customer = CustomerModel::where('user_id', $request->user()->id)->first();
        if ($pembelian->id_customer != $customer->id) return $this->sendError('Unauthorized', [], 403);

        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return $this->sendError('Pembayaran belum diselesaikan atau transaksi expired.', [], 403);
        }

        if (!$pembelian->stokAkun) {
            return $this->sendError('Kredensial akun tidak ditemukan. Harap hubungi admin.', [], 404);
        }

        return $this->sendResponse([
            'order_id' => $pembelian->order_id,
            'email_username' => $pembelian->stokAkun->email_username,
            'password' => $pembelian->stokAkun->password_encrypted,
            'catatan' => $pembelian->stokAkun->catatan,
        ], 'Kredensial akun berhasil diambil');
    }

    /**
     * Download File Digital
     */
    public function downloadDigital(Request $request, $order_id)
    {
        $pembelian = Pembelian::with(['varianLayanan.tipeLayanan.produk', 'customer'])
            ->where('order_id', $order_id)
            ->first();

        if (!$pembelian) return $this->sendError('Pesanan tidak ditemukan', [], 404);

        $customer = CustomerModel::where('user_id', $request->user()->id)->first();
        if ($pembelian->id_customer != $customer->id) return $this->sendError('Unauthorized', [], 403);

        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return $this->sendError('Pembayaran belum diselesaikan atau transaksi expired.', [], 403);
        }

        if ($pembelian->varianLayanan?->tipeLayanan?->produk?->tipe_produk !== 'digital') {
            return $this->sendError('Ini bukan produk digital.', [], 400);
        }

        if (empty($pembelian->varianLayanan->file_path)) {
            return $this->sendError('File belum tersedia. Silakan hubungi seller.', [], 404);
        }

        $privatePath = storage_path('app/private/file_digital/' . $pembelian->varianLayanan->file_path);
        $publicPath = public_path('assets/file_digital/' . $pembelian->varianLayanan->file_path);

        if (file_exists($privatePath)) {
            $filePath = $privatePath;
        } elseif (file_exists($publicPath)) {
            $filePath = $publicPath;
        } else {
            return $this->sendError('File digital tidak ditemukan di server.', [], 404);
        }

        $friendlyName = \Illuminate\Support\Str::slug($pembelian->varianLayanan->nama_varian) . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
        return response()->download($filePath, $friendlyName);
    }

    /**
     * Download Invoice PDF
     */
    public function downloadInvoice(Request $request, $order_id)
    {
        $pembelian = Pembelian::with([
            'customer.user',
            'varianLayanan.tipeLayanan.produk.toko',
            'pembayaran'
        ])
        ->where('order_id', $order_id)
        ->first();

        if (!$pembelian) return $this->sendError('Pesanan tidak ditemukan', [], 404);

        $customer = CustomerModel::where('user_id', $request->user()->id)->first();
        if ($pembelian->id_customer != $customer->id) return $this->sendError('Unauthorized', [], 403);

        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return $this->sendError('Invoice hanya tersedia untuk transaksi yang sudah sukses.', [], 400);
        }

        $pdf = Pdf::loadView('invoice.pdf_invoice', compact('pembelian'));
        
        return $pdf->download("invoice-{$order_id}.pdf");
    }

    /**
     * Buat Review Toko
     */
    public function storeReview(Request $request, $order_id)
    {
        $pembelian = Pembelian::with(['varianLayanan.tipeLayanan.produk.toko', 'review'])->where('order_id', $order_id)->first();
        if (!$pembelian) return $this->sendError('Pesanan tidak ditemukan', [], 404);

        $customer = CustomerModel::where('user_id', $request->user()->id)->first();
        if ($pembelian->id_customer != $customer->id) return $this->sendError('Unauthorized', [], 403);

        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return $this->sendError('Review hanya dapat diberikan untuk transaksi yang sukses.', [], 400);
        }

        if ($pembelian->review) {
            return $this->sendError('Kamu sudah memberikan review untuk transaksi ini.', [], 400);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $toko = $pembelian->varianLayanan->tipeLayanan->produk->toko;

        $review = Review::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_toko' => $toko->id_toko,
            'id_customer' => $pembelian->id_customer,
            'rating' => $request->rating,
            'komentar' => $request->komentar ? strip_tags($request->komentar) : null,
        ]);

        $toko->syncRatings();

        return $this->sendResponse($review, 'Review berhasil disimpan', 201);
    }

    /**
     * Get Daftar Laporan Customer
     */
    public function getLaporan(Request $request)
    {
        $userId = $request->user()->id;
        $perPage = (int) $request->input('per_page', 10);
        $laporan = Laporan::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->sendResponse($laporan, 'Daftar laporan berhasil diambil');
    }

    /**
     * Buat Laporan Customer
     */
    public function storeLaporan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $targetDir = public_path('assets/img/laporan');
            if (!file_exists($targetDir)) @mkdir($targetDir, 0755, true);
            $file->move($targetDir, $filename);
            $gambarPath = $filename;
        }

        $laporan = Laporan::create([
            'user_id' => $request->user()->id,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'gambar' => $gambarPath,
            'status' => 'pending'
        ]);

        return $this->sendResponse($laporan, 'Laporan berhasil dibuat', 201);
    }
}
