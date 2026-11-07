<?php

namespace App\Http\Controllers;

use App\Models\BeliProdukModel;
use App\Models\ProdukModel;
use App\Models\User;
use App\Models\CustomerModel;
use App\Models\PembayaranModel;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\MailProdukBeli;
use App\Services\PaymentProcessingService;

class PembayaranController extends Controller
{

    protected $paymentProcessor;

    public function __construct(PaymentProcessingService $paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;
    }

    private function syncTransactionStatus(string $order_id, $beli_produk = null)
    {
        $cacheKey = 'sync_status_' . $order_id;
        if (Cache::has($cacheKey)) {
            return null;
        }
        Cache::put($cacheKey, true, 20); // Cooldown 20 detik untuk mencegah API blocking delay

        // 1. Check if the order is for a Premium Account (Pembelian)
        $pembelian = \App\Models\Pembelian::where('order_id', $order_id)->first();
        if ($pembelian) {
            if ($pembelian->status == \App\Enums\PembelianStatus::SUCCESS) {
                return $pembelian;
            }

            try {
                $gatewayName = $pembelian->payment_gateway ?? 'midtrans';
                $gateway = \App\Services\Gateways\PaymentGatewayFactory::make($gatewayName);
                $statusData = $gateway->verifyStatus($order_id, (int)$pembelian->harga_saat_beli);
                
                if ($statusData['status'] === \App\Enums\PembelianStatus::SUCCESS) {
                    $this->paymentProcessor->markAsSuccess($pembelian, [
                        'payment_type' => $statusData['payment_type'] ?? 'unknown',
                        'payment_gateway' => $gatewayName,
                        'gross_amount' => $statusData['gross_amount'] ?? $pembelian->harga_saat_beli,
                        'transaction_id' => $statusData['transaction_id'] ?? null,
                    ]);
                } elseif (in_array($statusData['status'], [\App\Enums\PembelianStatus::FAILED, \App\Enums\PembelianStatus::EXPIRED])) {
                    $this->paymentProcessor->markAsFailed($pembelian, $statusData['status']->value ?? 'failed', $gatewayName);
                } elseif ($pembelian->reserved_until && $pembelian->reserved_until < now()) {
                    $this->paymentProcessor->markAsFailed($pembelian, 'expire', $gatewayName);
                }
            } catch (\Exception $e) {
                if ($pembelian->reserved_until && $pembelian->reserved_until < now()) {
                    $this->paymentProcessor->markAsFailed($pembelian, 'expire', $pembelian->payment_gateway ?? 'unknown');
                }
            }

            return $pembelian;
        }

        // 2. Otherwise fall back to old ZIP product order
        if (!$beli_produk) {
            $beli_produk = BeliProdukModel::where('order_id', $order_id)->first();
        }

        if (!$beli_produk || $beli_produk->status == 'success') {
            return $beli_produk;
        }

        try {
            $gateway = \App\Services\Gateways\PaymentGatewayFactory::make('midtrans');
            $statusData = $gateway->verifyStatus($order_id);
            if ($statusData['status'] === \App\Enums\PembelianStatus::SUCCESS) {
                $tanggal_saat_ini = date('Y-m-d');

                $pembayaranExists = PembayaranModel::where('order_id', $order_id)->exists();
                if (!$pembayaranExists) {
                    PembayaranModel::create([
                        'total' => $statusData['gross_amount'],
                        'metode' => $statusData['payment_type'] ?? 'midtrans',
                        'order_id' => $order_id
                    ]);
                }

                $beli_produk->update([
                    'status' => 'success',
                    'tanggal_transaksi' => $tanggal_saat_ini
                ]);

                // Send email
                $produk = ProdukModel::find($beli_produk->produk_id);
                $user = User::find($beli_produk->user_id);

                try {
                    Mail::to($user->email)->send(new MailProdukBeli(
                        $user->name,
                        $produk->nama_produk,
                        $produk->deskripsi,
                        $status->gross_amount,
                        $order_id
                    ));
                } catch (\Exception $mailEx) {
                    \Log::error('Failed to send mail in syncTransactionStatus: ' . $mailEx->getMessage());
                }
            } elseif (in_array($statusData['raw_status'], ['deny', 'expire', 'cancel'])) {
                $beli_produk->update(['status' => 'deny']);
            }
        } catch (\Exception $e) {
            // Ignored, order_id might not exist on Midtrans yet
        }

        return $beli_produk;
    }

    public function index()
    {
        return redirect()->route('premium.riwayat');
    }

    public function metode_pembayaran(Request $request, string $order_id)
    {
        $id = session('id');

        // Check if the order is for a Premium Account (Pembelian)
        $pembelian = \App\Models\Pembelian::where('order_id', $order_id)->first();
        if ($pembelian) {
            $this->authorize('view', $pembelian);
            // Sync status with Midtrans first
            $this->syncTransactionStatus($order_id);

            // Re-fetch
            $pembelian = \App\Models\Pembelian::where('order_id', $order_id)->first();

            if ($pembelian->status == \App\Enums\PembelianStatus::SUCCESS) {
                return redirect()->route('premium.riwayat')->with('success', 'Pembayaran berhasil dikonfirmasi.');
            }

            if (in_array($pembelian->status, [\App\Enums\PembelianStatus::EXPIRED, \App\Enums\PembelianStatus::CANCELLED, \App\Enums\PembelianStatus::FAILED]) || ($pembelian->reserved_until && $pembelian->reserved_until < now())) {
                if ($pembelian->status == \App\Enums\PembelianStatus::PENDING) {
                    $this->paymentProcessor->markAsFailed($pembelian, 'expire', $pembelian->payment_gateway ?? 'unknown');
                }
                return redirect()->route('premium.riwayat')->with('error', 'Batas waktu pembayaran untuk transaksi ini telah habis (transaksi dibatalkan).');
            }

            $user = User::find($id);
            $nomorTeleponCustomer = CustomerModel::where('user_id', $user->id)->first();
            $varian = \App\Models\VarianLayanan::findOrFail($pembelian->id_varian);
            $tipe = $varian->tipeLayanan;
            $produk = $tipe->produk;

            $pathId = $pembelian->order_id;
            $orderIdProduk = $pembelian->order_id;
            
            $reserved_expired_at = $pembelian->reserved_until;
            
            $hasActiveTransaction = false;
            $tripayActiveDetail = null;

            if ($pembelian->payment_gateway === 'pakasir' && $pembelian->gateway_reference && $reserved_expired_at > now()) {
                $hasActiveTransaction = true;
            } elseif ($pembelian->payment_gateway === 'tripay' && $pembelian->gateway_reference && $reserved_expired_at > now()) {
                $hasActiveTransaction = true;
                try {
                    $tripayGateway = \App\Services\Gateways\PaymentGatewayFactory::make('tripay');
                    $tripayActiveDetail = $tripayGateway->verifyStatus($pembelian->order_id, (int)$pembelian->harga_saat_beli);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to load active TriPay transaction details: ' . $e->getMessage());
                }
            }

            $tripayChannels = [];
            try {
                $tripayGateway = new \App\Services\Gateways\TriPayGateway();
                $tripayChannels = $tripayGateway->getPaymentChannels();
            } catch (\Exception $e) {
                // ignore
            }

            return view('pembayaran.metode_pembayaran', compact(
                'produk', 'pathId', 'orderIdProduk', 'user', 'nomorTeleponCustomer', 
                'pembelian', 'varian', 'reserved_expired_at', 'hasActiveTransaction',
                'tripayActiveDetail', 'tripayChannels'
            ));
        }

        // Old ZIP product order code follows...
        $beli_produk = BeliProdukModel::where('order_id', $order_id)->first();

        if (!$beli_produk) {
            return redirect()->route('premium.riwayat')->with('error', 'Order tidak ditemukan.');
        }

        if ($beli_produk->status == 'success') {
            return redirect()->route('premium.riwayat')->with('success', 'Pembayaran berhasil dikonfirmasi.');
        } else {
            $produk = ProdukModel::find($beli_produk->produk_id);
            $user = User::find($id);
            $nomorTeleponCustomer = CustomerModel::where('user_id', $user->id)->first();

            $items = array(
                array(
                    'id' => $produk->id_produk,
                    'price' => $produk->harga,
                    'quantity' => $beli_produk->qty,
                    'name' => $produk->nama_produk
                )
            );

            $params = array(
                'item_details' => $items,
                'transaction_details' => array(
                    'order_id' => $beli_produk->order_id,
                    'gross_amount' => $produk->harga,
                ),
                'customer_details' => array(
                    'first_name' => $user->name,
                    'phone' => $nomorTeleponCustomer->nomor_telepon,
                ),
                'callbacks' => array(
                    'finish' => route('bukti_pembayaran.status', ['order_id' => $beli_produk->order_id])
                )
            );

            $pathId = $beli_produk->order_id;
            $orderIdProduk = $beli_produk->order_id;

            try {
                $snapToken = \Midtrans\Snap::getSnapToken($params);
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'has already been taken')) {
                    try {
                        $status = \Midtrans\Transaction::status($orderIdProduk);
                        if ($status->transaction_status == 'pending') {
                            return redirect()->route('premium.riwayat')->with('error', 'Pembayaran sedang ditangguhkan (pending) di Midtrans. Harap selesaikan pembayaran Anda.');
                        }
                    } catch (\Exception $statusEx) {
                        // ignore status check failure
                    }
                }
                return redirect()->route('premium.riwayat')->with('error', 'Gagal memproses pembayaran Midtrans: ' . $e->getMessage());
            }

            return view('pembayaran.metode_pembayaran', compact('produk', 'snapToken', 'pathId', 'orderIdProduk', 'user', 'nomorTeleponCustomer'));
        }
    }

    public function generate_transaksi(Request $request, string $order_id)
    {
        $id = session('id');
        $pembelian = \App\Models\Pembelian::where('order_id', $order_id)->first();
        
        if (!$pembelian) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        $this->authorize('view', $pembelian);

        $gateway_name = strtolower($request->input('gateway', 'midtrans'));

        if (!\App\Models\SettingWebsite::isGatewayActive($gateway_name)) {
            return response()->json([
                'error' => 'Gateway pembayaran ' . strtoupper($gateway_name) . ' sedang dinonaktifkan oleh admin.'
            ], 422);
        }
        
        // Prevent re-creating if active Pakasir transaction exists
        if ($gateway_name === 'pakasir' && $pembelian->payment_gateway === 'pakasir' && $pembelian->gateway_reference && $pembelian->reserved_until > now()) {
            return response()->json(['success' => true, 'gateway' => 'pakasir']);
        }

        // Prevent re-creating if active TriPay transaction exists
        if ($gateway_name === 'tripay' && $pembelian->payment_gateway === 'tripay' && $pembelian->gateway_reference && $pembelian->reserved_until > now()) {
            return response()->json(['success' => true, 'gateway' => 'tripay']);
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
                
                return response()->json([
                    'success' => true,
                    'gateway' => 'pakasir',
                    'redirect_url' => $redirectUrl
                ]);
            } elseif ($gateway_name === 'tripay') {
                $channel = $request->input('channel', 'QRIS');
                $gateway = \App\Services\Gateways\PaymentGatewayFactory::make('tripay');
                $transactionData = $gateway->createTransaction($pembelian, $channel);

                return response()->json([
                    'success' => true,
                    'gateway' => 'tripay',
                    'data' => $transactionData
                ]);
            } else {
                $gateway = \App\Services\Gateways\PaymentGatewayFactory::make($gateway_name);
                $transactionData = $gateway->createTransaction($pembelian, 'qris');
                
                return response()->json([
                    'success' => true,
                    'gateway' => 'midtrans',
                    'snapToken' => $transactionData['token'] ?? null
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('generate_transaksi error', [
                'order_id' => $order_id,
                'gateway' => $gateway_name,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Pembayaran gagal dibuat, silakan coba lagi.'], 500);
        }
    }

    public function download_bukti_pembayaran(string $order_id)
    {
        $user = Auth::user();
        if (!$user && session()->has('id')) {
            $user = User::find(session('id'));
        }

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $pembelian = \App\Models\Pembelian::where('order_id', $order_id)->first();
        if ($pembelian) {
            $this->authorize('view', $pembelian);
        } else {
            $beliProduk = \App\Models\BeliProdukModel::where('order_id', $order_id)->first();
            if (!$beliProduk || ($beliProduk->user_id != $user->id && $user->role_id != 1)) {
                abort(403, 'Unauthorized access.');
            }
        }

        $pembayaran = PembayaranModel::where('order_id', $order_id)->first();
        if (!$pembayaran) {
            abort(404, 'Data pembayaran tidak ditemukan.');
        }

        $produk = ProdukModel::withWhereHas('produk_beli', function ($query) use ($order_id) {
            $query->where('order_id', $order_id);
        })->get();

        $invoice = 'invoice-' . $pembayaran->order_id . '.pdf';
        $pdf = Pdf::loadView('pembayaran.download_bukti_pembayaran', compact('user', 'produk', 'pembayaran'));
        return $pdf->download($invoice);
    }

    public function status(string $order_id)
    {
        // 1. Sync status dengan Midtrans terlebih dahulu via API backend (reload-safe/bookmarkable)
        $this->syncTransactionStatus($order_id);

        // 2. Query Pembelian (Premium Account)
        $pembelian = \App\Models\Pembelian::with(['varianLayanan.tipeLayanan.produk', 'pembayaran'])
            ->where('order_id', $order_id)
            ->first();

        if ($pembelian) {
            $this->authorize('view', $pembelian);
            return view('customer.status_pembayaran', [
                'type' => 'premium',
                'order' => $pembelian,
                'orderId' => $order_id,
                'status' => strtolower($pembelian->status->value ?? $pembelian->status),
            ]);
        }

        // 3. Fallback ke Legacy BeliProdukModel (Zip Product)
        $beli_produk = BeliProdukModel::with('produk')->where('order_id', $order_id)->first();
        if ($beli_produk) {
            if ($beli_produk->user_id != auth()->id()) {
                abort(403, 'Unauthorized access.');
            }
            return view('customer.status_pembayaran', [
                'type' => 'legacy',
                'order' => $beli_produk,
                'orderId' => $order_id,
                'status' => strtolower($beli_produk->status),
            ]);
        }

        abort(404, 'Transaksi tidak ditemukan.');
    }

    public function statusApi(string $order_id)
    {
        // 1. Query ringan data Pembelian (hanya kolom yang diperlukan, memanfaatkan index order_id)
        $pembelian = \App\Models\Pembelian::select('id_pembelian', 'order_id', 'id_customer', 'status', 'updated_at')
            ->where('order_id', $order_id)
            ->first();

        if ($pembelian) {
            try {
                // Cek otorisasi kepemilikan transaksi dengan Policy
                $this->authorize('view', $pembelian);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Jika status masih pending, sinkronkan dengan payment gateway (dengan cooldown)
            $statusVal = strtolower($pembelian->status->value ?? $pembelian->status);
            if ($statusVal === 'pending') {
                $this->syncTransactionStatus($order_id);
                $pembelian->refresh();
                $statusVal = strtolower($pembelian->status->value ?? $pembelian->status);
            }

            return response()->json([
                'status' => $statusVal,
                'updated_at' => $pembelian->updated_at ? $pembelian->updated_at->toIso8601String() : null,
            ]);
        }

        // 2. Fallback ke Legacy BeliProdukModel (Zip Product)
        $beli_produk = BeliProdukModel::select('id', 'order_id', 'user_id', 'status', 'tanggal_transaksi')
            ->where('order_id', $order_id)
            ->first();

        if ($beli_produk) {
            if ($beli_produk->user_id != auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if (strtolower($beli_produk->status) !== 'success') {
                $this->syncTransactionStatus($order_id, $beli_produk);
                $beli_produk->refresh();
            }

            return response()->json([
                'status' => strtolower($beli_produk->status),
                'updated_at' => $beli_produk->tanggal_transaksi ? date('c', strtotime($beli_produk->tanggal_transaksi)) : null,
            ]);
        }

        return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
    }

    public function checkout_status(string $invoice_number)
    {
        // Query data Pembelian (Premium Account) - TANPA memanggil API Gateway
        $pembelian = \App\Models\Pembelian::with(['varianLayanan.tipeLayanan.produk', 'pembayaran'])
            ->where('order_id', $invoice_number)
            ->first();

        if ($pembelian) {
            $this->authorize('view', $pembelian);
            return view('customer.status_pembayaran', [
                'type' => 'premium',
                'order' => $pembelian,
                'orderId' => $invoice_number,
                'status' => strtolower($pembelian->status->value ?? $pembelian->status),
                'is_db_only' => true,
            ]);
        }

        abort(404, 'Transaksi tidak ditemukan.');
    }

    public function checkoutStatusApi(string $invoice_number)
    {
        // Query data Pembelian (Premium Account) - TANPA memanggil API Gateway
        $pembelian = \App\Models\Pembelian::where('order_id', $invoice_number)->first();
        
        if ($pembelian) {
            try {
                $this->authorize('view', $pembelian);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json([
                'status' => strtolower($pembelian->status->value ?? $pembelian->status),
                'updated_at' => $pembelian->updated_at ? $pembelian->updated_at->toIso8601String() : null,
            ]);
        }

        return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
    }
}
