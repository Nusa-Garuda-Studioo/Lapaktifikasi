<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\CustomerModel;
use App\Models\StokAkun;
use App\Models\VarianLayanan;
use App\Models\Voucher;
use App\Models\VoucherKlaim;
use App\Enums\PembelianStatus;
use App\Enums\StokStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Str;
use App\Services\PaymentProcessingService;
use App\Services\Gateways\PaymentGatewayFactory;

class CheckoutController extends ApiController
{
    protected $paymentProcessor;

    public function __construct(PaymentProcessingService $paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;
    }

    /**
     * Proses Checkout (Pilih Varian & Voucher)
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'id_varian' => 'required|exists:tbl_varian_layanan,id_varian',
            'kode_voucher' => 'nullable|string'
        ]);

        $id_varian = $request->id_varian;
        $kode_voucher = strtoupper(trim((string) $request->kode_voucher));
        $idCustomerUser = $request->user()->id;

        $customer = CustomerModel::where('user_id', $idCustomerUser)->first();
        if (!$customer) {
            return $this->sendError('Customer profile not found.', [], 404);
        }

        if (empty($customer->nomor_telepon) || empty($customer->user->name)) {
            return $this->sendError('Silakan lengkapi nama dan nomor telepon WhatsApp Anda di profil terlebih dahulu sebelum melakukan pembelian.', [], 400);
        }

        $id_customer = $customer->id;
        $lock = null;

        try {
            try {
                $lock = Cache::lock("checkout_varian_{$id_varian}", 10);
                $lock->block(5);
            } catch (\Throwable $e) {
                // fallback if lock is not supported by driver
            }

            $pembelian = DB::transaction(function () use ($id_varian, $id_customer, $kode_voucher) {
                $varian = VarianLayanan::with('tipeLayanan.produk')->findOrFail($id_varian);
                $isDigital = ($varian->tipeLayanan?->produk?->tipe_produk === 'digital');

                $stok = null;
                if (!$isDigital) {
                    $stok = StokAkun::where('id_varian', $id_varian)
                        ->where('status', StokStatus::TERSEDIA)
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->first();

                    if (!$stok) {
                        throw new \Exception('Stok Akun Habis');
                    }
                }

                $harga_varian = (float) $varian->harga;
                $harga_saat_beli = $harga_varian;
                $nominal_diskon = 0;
                $voucher_dipakai = null;

                if (!empty($kode_voucher)) {
                    $voucher = Voucher::where('kode', $kode_voucher)->lockForUpdate()->first();

                    if (!$voucher || !$voucher->is_active) {
                        throw new \Exception("Kode voucher '{$kode_voucher}' tidak valid atau tidak aktif.");
                    }

                    $now = now();
                    if ($voucher->berlaku_dari && $now->lt($voucher->berlaku_dari)) throw new \Exception("Voucher belum berlaku.");
                    if ($voucher->berlaku_sampai && $now->gt($voucher->berlaku_sampai)) throw new \Exception("Voucher sudah kedaluwarsa.");
                    if ($voucher->kuota_total !== null && $voucher->kuota_terpakai >= $voucher->kuota_total) throw new \Exception("Kuota voucher telah habis.");

                    $idTokoProduk = $varian->tipeLayanan?->produk?->id_toko;
                    if ($voucher->scope === 'toko_spesifik' && $voucher->id_toko != $idTokoProduk) throw new \Exception("Voucher tidak berlaku untuk toko produk ini.");

                    if ($harga_varian < (float) $voucher->minimal_transaksi) {
                        throw new \Exception("Minimal transaksi tidak terpenuhi.");
                    }

                    $existingKlaim = VoucherKlaim::where('id_voucher', $voucher->id_voucher)->where('id_customer', $id_customer)->lockForUpdate()->first();
                    if (!$existingKlaim) {
                        VoucherKlaim::create([
                            'id_voucher' => $voucher->id_voucher,
                            'id_customer' => $id_customer,
                            'id_pembelian' => null,
                            'created_at' => now(),
                        ]);
                    }

                    if ($voucher->tipe_diskon === 'persen' || $voucher->tipe_diskon === 'persentase') {
                        $potongan = ($harga_varian * (float) $voucher->nilai_diskon) / 100.0;
                        if ($voucher->maksimal_potongan !== null) $potongan = min($potongan, (float) $voucher->maksimal_potongan);
                    } else {
                        $potongan = (float) $voucher->nilai_diskon;
                    }

                    $nominal_diskon = min($harga_varian, max(0.0, $potongan));
                    $harga_saat_beli = max(0.0, $harga_varian - $nominal_diskon);
                    $voucher_dipakai = $voucher->id_voucher;
                }

                $reserved_expired_at = now()->addMinutes(15);

                if ($stok) {
                    $stok->update([
                        'status' => StokStatus::RESERVED,
                        'reserved_at' => now(),
                        'reserved_expired_at' => $reserved_expired_at,
                    ]);
                }

                $pembelian = Pembelian::create([
                    'order_id' => (string) Str::ulid(),
                    'id_customer' => $id_customer,
                    'id_varian' => $id_varian,
                    'id_stok' => $stok?->id_stok,
                    'harga_saat_beli' => $harga_saat_beli,
                    'id_voucher_dipakai' => $voucher_dipakai,
                    'nominal_diskon' => $nominal_diskon,
                    'status' => PembelianStatus::PENDING,
                    'reserved_until' => $reserved_expired_at,
                ]);

                if ($stok) {
                    $stok->update(['id_pembelian' => $pembelian->id_pembelian]);
                }

                return $pembelian;
            });

            return $this->sendResponse($pembelian, 'Checkout berhasil, silakan lanjutkan pembayaran', 201);

        } catch (LockTimeoutException $e) {
            return $this->sendError('Sistem sedang sibuk memproses pesanan varian ini, silakan coba beberapa detik lagi.', [], 429);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 400);
        } finally {
            if ($lock) {
                optional($lock)->release();
            }
        }
    }

    /**
     * Generate Instruksi Transaksi (Midtrans / TriPay / Pakasir)
     */
    public function generateTransaction(Request $request, $order_id)
    {
        $pembelian = Pembelian::where('order_id', $order_id)->first();
        if (!$pembelian) return $this->sendError('Pesanan tidak ditemukan', [], 404);

        $customer = CustomerModel::where('user_id', $request->user()->id)->first();
        if ($pembelian->id_customer != $customer->id) return $this->sendError('Unauthorized', [], 403);

        $gateway_name = strtolower($request->input('gateway', 'midtrans'));

        if (!\App\Models\SettingWebsite::isGatewayActive($gateway_name)) {
            return $this->sendError('Gateway pembayaran ' . strtoupper($gateway_name) . ' sedang dinonaktifkan oleh admin.', [], 422);
        }

        if ($gateway_name === 'pakasir' && $pembelian->payment_gateway === 'pakasir' && $pembelian->gateway_reference && $pembelian->reserved_until > now()) {
            return $this->sendResponse(['redirect_url' => $pembelian->gateway_reference], 'Transaksi sudah aktif');
        }

        if ($gateway_name === 'tripay' && $pembelian->payment_gateway === 'tripay' && $pembelian->gateway_reference && $pembelian->reserved_until > now()) {
            return $this->sendResponse(['reference' => $pembelian->gateway_reference], 'Transaksi TriPay sudah aktif');
        }

        $pembelian->payment_gateway = $gateway_name;
        $pembelian->save();

        try {
            if ($gateway_name === 'pakasir') {
                $slug = config('pakasir.project_slug');
                $amount = (int) $pembelian->harga_saat_beli;
                $returnUrl = urlencode(route('checkout.status', ['invoice_number' => $order_id]));
                $redirectUrl = rtrim(config('pakasir.base_url', 'https://app.pakasir.com'), '/') . "/pay/{$slug}/{$amount}?order_id={$order_id}&redirect={$returnUrl}";
                
                $pembelian->gateway_reference = 'redirect';
                $pembelian->save();
                
                return $this->sendResponse(['redirect_url' => $redirectUrl, 'gateway' => 'pakasir'], 'Generate Pakasir url success');
            } elseif ($gateway_name === 'tripay') {
                $channel = $request->input('channel', 'QRIS');
                $gateway = PaymentGatewayFactory::make('tripay');
                $transactionData = $gateway->createTransaction($pembelian, $channel);

                return $this->sendResponse(['data' => $transactionData, 'gateway' => 'tripay'], 'Generate TriPay transaction success');
            } else {
                $gateway = PaymentGatewayFactory::make($gateway_name);
                $transactionData = $gateway->createTransaction($pembelian, 'qris');
                
                return $this->sendResponse(['snapToken' => $transactionData['token'] ?? null, 'gateway' => 'midtrans'], 'Generate Midtrans token success');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Api generateTransaction error', [
                'order_id' => $order_id,
                'gateway' => $gateway_name,
                'error' => $e->getMessage()
            ]);
            return $this->sendError('Pembayaran gagal dibuat, silakan coba lagi.', [], 500);
        }
    }

    /**
     * Check status pembayaran & sync gateway
     */
    public function status(Request $request, $order_id)
    {
        $pembelian = Pembelian::with(['varianLayanan.tipeLayanan.produk.toko', 'pembayaran'])
            ->where('order_id', $order_id)
            ->first();
            
        if (!$pembelian) return $this->sendError('Pesanan tidak ditemukan', [], 404);

        $customer = CustomerModel::where('user_id', $request->user()->id)->first();
        if ($pembelian->id_customer != $customer->id) return $this->sendError('Unauthorized', [], 403);

        // Sync logic
        if ($pembelian->status == PembelianStatus::PENDING) {
            try {
                $gatewayName = $pembelian->payment_gateway ?? 'midtrans';
                $gateway = PaymentGatewayFactory::make($gatewayName);
                $statusData = $gateway->verifyStatus($order_id, (int)$pembelian->harga_saat_beli);
                
                if ($statusData['status'] === PembelianStatus::SUCCESS) {
                    $this->paymentProcessor->markAsSuccess($pembelian, [
                        'payment_type' => $statusData['payment_type'] ?? 'unknown',
                        'payment_gateway' => $gatewayName,
                        'gross_amount' => $statusData['gross_amount'] ?? $pembelian->harga_saat_beli,
                        'transaction_id' => $statusData['transaction_id'] ?? null,
                    ]);
                } elseif (in_array($statusData['status'], [PembelianStatus::FAILED, PembelianStatus::EXPIRED])) {
                    $this->paymentProcessor->markAsFailed($pembelian, $statusData['status']->value ?? 'failed', $gatewayName);
                } elseif ($pembelian->reserved_until && $pembelian->reserved_until < now()) {
                    $this->paymentProcessor->markAsFailed($pembelian, 'expire', $gatewayName);
                }
                
                // Re-fetch
                $pembelian = Pembelian::with(['varianLayanan.tipeLayanan.produk.toko', 'pembayaran'])->where('order_id', $order_id)->first();
            } catch (\Exception $e) {
                if ($pembelian->reserved_until && $pembelian->reserved_until < now()) {
                    $this->paymentProcessor->markAsFailed($pembelian, 'expire', $pembelian->payment_gateway ?? 'unknown');
                    $pembelian = Pembelian::with(['varianLayanan.tipeLayanan.produk.toko', 'pembayaran'])->where('order_id', $order_id)->first();
                }
            }
        }

        return $this->sendResponse([
            'order_id' => $pembelian->order_id,
            'status' => strtolower($pembelian->status->value ?? $pembelian->status),
            'harga' => $pembelian->harga_saat_beli,
            'nominal_diskon' => $pembelian->nominal_diskon,
            'payment_gateway' => $pembelian->payment_gateway,
            'produk' => $pembelian->varianLayanan?->tipeLayanan?->produk?->nama_produk,
            'tipe' => $pembelian->varianLayanan?->tipeLayanan?->nama_tipe,
            'varian' => $pembelian->varianLayanan?->nama_varian,
            'tipe_produk' => $pembelian->varianLayanan?->tipeLayanan?->produk?->tipe_produk,
            'updated_at' => $pembelian->updated_at,
        ], 'Status pembayaran');
    }
}
