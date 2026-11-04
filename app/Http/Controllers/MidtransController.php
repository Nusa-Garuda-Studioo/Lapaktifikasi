<?php

namespace App\Http\Controllers;

use App\Enums\PembelianStatus;
use App\Enums\StokStatus;
use App\Jobs\SendAccountInvoiceWhatsapp;
use App\Mail\MailProdukBeli;
use App\Models\BeliProdukModel;
use App\Models\Pembayaran;
use App\Models\PembayaranModel;
use App\Models\Pembelian;
use App\Models\ProdukModel;
use App\Models\StokAkun;
use App\Models\User;
use App\Services\PaymentProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MidtransController extends Controller
{
    protected $paymentProcessor;

    public function __construct(PaymentProcessingService $paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;
    }

    public function callback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);
        $tanggal_saat_ini = date('Y-m-d');
        $tipePembayaran = $request->payment_type;
        $total = $request->gross_amount;
        $order_id = $request->order_id;

        \App\Models\MidtransWebhookLog::create([
            'order_id' => $order_id,
            'status_code' => $request->status_code,
            'signature_key' => $request->signature_key,
            'payload' => $request->all(),
        ]);

        if (!hash_equals($hashed, (string) $request->signature_key)) {
            Log::warning('Midtrans webhook invalid signature', ['order_id' => $order_id]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        try {
            $gateway = \App\Services\Gateways\PaymentGatewayFactory::make('midtrans');
            $statusData = $gateway->verifyStatus($order_id);
            $transaction_status = $statusData['raw_status'];
            $fraud_status = $statusData['fraud_status'] ?? null;
        } catch (\Exception $e) {
            Log::error('Midtrans status check failed in webhook', ['order_id' => $order_id, 'error' => $e->getMessage()]);
            $transaction_status = $request->transaction_status;
            $fraud_status = $request->fraud_status;
        }

        $pembelian = Pembelian::where('order_id', $order_id)->first();

        if ($pembelian) {
            return $this->handlePremiumCallback($request, $pembelian, $order_id, $transaction_status, $fraud_status);
        }

        if (BeliProdukModel::where('order_id', $order_id)->exists()) {
            return $this->handleLegacyZipCallback($request, $order_id, $tipePembayaran, $total, $tanggal_saat_ini);
        }

        return response()->json([
            'message' => 'Pembelian dengan order_id "' . $order_id . '" tidak ditemukan.',
        ], 404);
    }

    private function handlePremiumCallback(Request $request, Pembelian $pembelian, string $order_id, $transaction_status = null, $fraud_status = null)
    {
        if ($pembelian->status === PembelianStatus::SUCCESS) {
            return response()->json(['message' => 'already processed']);
        }

        $transaction_status = $transaction_status ?? $request->transaction_status;
        $fraud_status = $fraud_status ?? $request->fraud_status;

        $isPaymentSuccess = $transaction_status === 'settlement'
            || ($transaction_status === 'capture' && $fraud_status === 'accept');

        if ($isPaymentSuccess) {
            $grossAmount = (int) $request->gross_amount;
            if ($grossAmount > 0 && (int) $pembelian->harga_saat_beli !== $grossAmount) {
                Log::warning('Midtrans webhook rejected: gross_amount mismatch', [
                    'order_id' => $order_id,
                    'db_amount' => $pembelian->harga_saat_beli,
                    'webhook_amount' => $grossAmount,
                ]);
                return response()->json(['message' => 'Gross amount mismatch'], 400);
            }

            $this->paymentProcessor->markAsSuccess($pembelian, [
                'payment_type' => $request->payment_type,
                'payment_gateway' => 'midtrans',
                'gross_amount' => $request->gross_amount,
                'transaction_id' => $request->transaction_id,
            ]);

            return response()->json(['message' => 'payment processed']);
        }

        if (in_array($request->transaction_status, ['deny', 'expire', 'cancel'], true)) {
            $this->paymentProcessor->markAsFailed($pembelian, $request->transaction_status, 'midtrans');

            return response()->json(['message' => 'payment ' . $request->transaction_status]);
        }

        return response()->json(['message' => 'notification received']);
    }

    private function handleLegacyZipCallback(Request $request, string $order_id, $tipePembayaran, $total, string $tanggal_saat_ini)
    {
        if ($request->transaction_status == 'capture' || $request->transaction_status == 'settlement') {
            if ($request->transaction_status == 'settlement' || ($request->transaction_status == 'capture' && $request->fraud_status == 'accept')) {
                $pembayaranProduk = PembayaranModel::create([
                    'total' => $total,
                    'metode' => $tipePembayaran,
                    'order_id' => $order_id,
                ]);

                BeliProdukModel::where('order_id', $order_id)
                    ->update(['status' => 'success', 'tanggal_transaksi' => $tanggal_saat_ini]);

                $detail = BeliProdukModel::where('order_id', $order_id)->first();
                $produk = ProdukModel::find($detail->produk_id);
                $user = User::find($detail->user_id);

                Mail::to($user->email)->send(new MailProdukBeli(
                    $user->name,
                    $produk->nama_produk,
                    $produk->deskripsi,
                    $pembayaranProduk->total,
                    $order_id
                ));
            }
        } elseif ($request->transaction_status == 'deny') {
            BeliProdukModel::where('order_id', $order_id)
                ->update(['status' => 'deny']);
        }
    }
}
