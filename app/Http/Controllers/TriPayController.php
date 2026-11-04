<?php

namespace App\Http\Controllers;

use App\Enums\PembelianStatus;
use App\Models\Pembelian;
use App\Models\TripayWebhookLog;
use App\Services\PaymentProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TriPayController extends Controller
{
    protected PaymentProcessingService $paymentProcessor;

    public function __construct(PaymentProcessingService $paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;
    }

    /**
     * Handle incoming callback from TriPay.
     */
    public function callback(Request $request)
    {
        $rawContent = $request->getContent();
        $callbackSignature = $request->header('X-Callback-Signature');
        $callbackEvent = $request->header('X-Callback-Event');

        $privateKey = config('tripay.private_key');

        // 1. Signature Validation (timing-safe check before processing anything)
        if (empty($privateKey) || empty($callbackSignature)) {
            Log::warning('TriPay webhook rejected: missing private key or signature header', [
                'ip' => $request->ip(),
                'has_header' => !empty($callbackSignature),
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        $expectedSignature = hash_hmac('sha256', $rawContent, $privateKey);

        if (!hash_equals($expectedSignature, (string) $callbackSignature)) {
            Log::warning('TriPay webhook rejected: signature mismatch', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        // 2. Decode and Validate JSON Payload
        $payload = json_decode($rawContent, true);
        if (!is_array($payload)) {
            Log::warning('TriPay webhook rejected: invalid JSON payload', ['ip' => $request->ip()]);
            return response()->json(['success' => false, 'message' => 'Invalid JSON payload'], 400);
        }

        // Check if event is payment_status
        if ($callbackEvent && $callbackEvent !== 'payment_status') {
            Log::info('TriPay webhook received unhandled event', ['event' => $callbackEvent]);
            return response()->json(['success' => true, 'message' => 'Event ignored']);
        }

        $reference = $payload['reference'] ?? null;
        $merchantRef = $payload['merchant_ref'] ?? null;
        $rawStatus = strtoupper($payload['status'] ?? '');
        $totalAmount = (int) ($payload['total_amount'] ?? 0);
        $paymentMethod = $payload['payment_method'] ?? 'TriPay';

        // 3. Save raw webhook log for audit trail
        $log = TripayWebhookLog::create([
            'reference' => $reference,
            'merchant_ref' => $merchantRef,
            'amount' => $totalAmount,
            'status' => $rawStatus,
            'payload' => $payload,
        ]);

        try {
            // 4. Find Order by merchant_ref (internal order_id ULID)
            $pembelian = Pembelian::where('order_id', $merchantRef)->first();

            if (!$pembelian) {
                Log::warning('TriPay webhook rejected: order_id not found in database', [
                    'merchant_ref' => $merchantRef,
                    'reference' => $reference,
                ]);
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            // Verify reference consistency if already recorded
            if (!empty($pembelian->gateway_reference) && $pembelian->gateway_reference !== $reference) {
                Log::warning('TriPay webhook reference inconsistency', [
                    'order_id' => $merchantRef,
                    'db_reference' => $pembelian->gateway_reference,
                    'webhook_reference' => $reference,
                ]);
            }

            // Verify amount consistency
            if ($totalAmount > 0 && (int) $pembelian->harga_saat_beli !== $totalAmount) {
                Log::warning('TriPay webhook rejected: amount mismatch', [
                    'order_id' => $merchantRef,
                    'db_amount' => $pembelian->harga_saat_beli,
                    'webhook_amount' => $totalAmount,
                ]);
                return response()->json(['success' => false, 'message' => 'Amount mismatch'], 400);
            }

            // 5. Idempotency Guard (If already SUCCESS, do not repeat side effects)
            if ($pembelian->status === PembelianStatus::SUCCESS) {
                Log::info('TriPay webhook: order already in SUCCESS state, skipping duplicate processing', [
                    'order_id' => $merchantRef,
                    'reference' => $reference,
                ]);
                $log->update(['verified_at' => now()]);
                return response()->json(['success' => true, 'message' => 'already processed']);
            }

            // 6. Map Status & Execute Atomic Processing
            switch ($rawStatus) {
                case 'PAID':
                    $this->paymentProcessor->markAsSuccess($pembelian, [
                        'payment_type' => $paymentMethod,
                        'payment_gateway' => 'tripay',
                        'gross_amount' => $totalAmount ?: $pembelian->harga_saat_beli,
                        'transaction_id' => $reference,
                    ]);
                    $log->update(['verified_at' => now()]);
                    Log::info('TriPay webhook: order marked as SUCCESS', [
                        'order_id' => $merchantRef,
                        'reference' => $reference,
                    ]);
                    break;

                case 'EXPIRED':
                    $this->paymentProcessor->markAsFailed($pembelian, 'expire', 'tripay');
                    $log->update(['verified_at' => now()]);
                    Log::info('TriPay webhook: order marked as EXPIRED', [
                        'order_id' => $merchantRef,
                        'reference' => $reference,
                    ]);
                    break;

                case 'FAILED':
                case 'REFUND':
                    $this->paymentProcessor->markAsFailed($pembelian, strtolower($rawStatus), 'tripay');
                    $log->update(['verified_at' => now()]);
                    Log::info("TriPay webhook: order marked as {$rawStatus}", [
                        'order_id' => $merchantRef,
                        'reference' => $reference,
                    ]);
                    break;

                case 'UNPAID':
                default:
                    $log->update(['verified_at' => now()]);
                    Log::info("TriPay webhook: order status is {$rawStatus}, no state transition needed", [
                        'order_id' => $merchantRef,
                        'reference' => $reference,
                    ]);
                    break;
            }

            // 7. Acknowledge with official TriPay response
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('TriPay webhook error during processing', [
                'order_id' => $merchantRef,
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }
}
