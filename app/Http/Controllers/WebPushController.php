<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WebPushController extends Controller
{
    /**
     * Pastikan konfigurasi OpenSSL berjalan mulus di environment Windows/Laragon.
     */
    private function bootstrapOpenSSL()
    {
        $possibleCnf = [
            'C:/laragon/bin/php/php-8.3.23-Win32-vs16-x64/extras/ssl/openssl.cnf',
            'C:/laragon/etc/ssl/openssl.cnf',
            'C:/laragon/bin/apache/httpd-2.4.54-win64-VS16/conf/openssl.cnf',
        ];
        foreach ($possibleCnf as $cnf) {
            if (file_exists($cnf) && !getenv('OPENSSL_CONF')) {
                putenv("OPENSSL_CONF=$cnf");
                $_ENV['OPENSSL_CONF'] = $cnf;
                break;
            }
        }
    }

    /**
     * Dapatkan instance WebPush yang terautentikasi dengan kunci VAPID.
     */
    private function getWebPushInstance(): WebPush
    {
        $this->bootstrapOpenSSL();

        if (!class_exists('Minishlink\WebPush\WebPush')) {
            $packageAutoload = app_path('Packages/WebPush/autoload.php');
            if (file_exists($packageAutoload)) {
                require_once $packageAutoload;
            }
            $autoload = base_path('vendor/autoload.php');
            if (file_exists($autoload)) {
                require_once $autoload;
            }
        }

        $auth = [
            'VAPID' => [
                'subject' => config('webpush.vapid.subject', 'mailto:support@lapaktifikasi.com'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        return new WebPush($auth);
    }

    /**
     * Endpoint untuk mengambil Public VAPID Key ke frontend.
     */
    public function getPublicKey()
    {
        return response()->json([
            'status' => 'success',
            'publicKey' => config('webpush.vapid.public_key'),
        ]);
    }

    /**
     * Daftarkan atau perbarui langganan Push perangkat browser.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|url:https',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string',
        ]);

        $endpoint = $validated['endpoint'];
        $endpointHash = hash('sha256', $endpoint);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'user_id' => Auth::check() ? Auth::id() : null,
                'endpoint' => $endpoint,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mendaftarkan langganan Push Notification.',
            'id' => $subscription->id,
        ]);
    }

    /**
     * Batalkan langganan notifikasi untuk endpoint tertentu.
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $endpointHash = hash('sha256', $validated['endpoint']);
        PushSubscription::where('endpoint_hash', $endpointHash)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Langganan notifikasi berhasil dihapus.',
        ]);
    }

    /**
     * Kirim notifikasi uji coba langsung ke perangkat yang meminta.
     */
    public function sendTestNotification(Request $request)
    {
        $endpoint = $request->input('endpoint');
        $subscription = null;

        if ($endpoint) {
            $endpointHash = hash('sha256', $endpoint);
            $subscription = PushSubscription::where('endpoint_hash', $endpointHash)->first();
        }

        if (!$subscription && Auth::check()) {
            $subscription = PushSubscription::where('user_id', Auth::id())->latest()->first();
        }

        if (!$subscription) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ditemukan perangkat aktif yang terdaftar untuk dikirimkan notifikasi. Silakan aktifkan notifikasi terlebih dahulu.',
            ], 404);
        }

        try {
            $webPush = $this->getWebPushInstance();

            $sub = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?? 'aes128gcm',
            ]);

            $payload = json_encode([
                'title' => '⚡ Notifikasi Lapaktifikasi Aktif!',
                'body' => 'Web Push berhasil terhubung. Anda akan menerima update pesanan & promo langsung di HP.',
                'icon' => asset('assets/img/pwa/icon-192x192.png'),
                'badge' => asset('assets/img/pwa/icon-96x96.png'),
                'url' => url('/premium/katalog'),
                'timestamp' => time(),
            ]);

            $report = $webPush->sendOneNotification($sub, $payload);

            if ($report->isSuccess()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Push notification berhasil dikirim ke perangkat Anda!',
                ]);
            } else {
                if ($report->isSubscriptionExpired() && $subscription->exists) {
                    $subscription->delete();
                }
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengirim push: ' . $report->getReason(),
                ], 400);
            }
        } catch (\Throwable $e) {
            Log::error('WebPush Test Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Broadcast Push Notification dari Admin ke seluruh pengguna atau segmen tertentu.
     */
    public function broadcast(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'target' => 'nullable|string|in:all,customer,seller',
        ]);

        $query = PushSubscription::query();
        $target = $validated['target'] ?? 'all';

        if ($target === 'customer') {
            // Target customer mencakup user dengan role_id = 2 serta pengunjung umum (guest / belum login)
            $query->where(function ($q) {
                $q->whereHas('user', function ($subQ) {
                    $subQ->where('role_id', 2);
                })->orWhereNull('user_id');
            });
        } elseif ($target === 'seller') {
            // Target khusus mitra seller (role_id = 3)
            $query->whereHas('user', function ($q) {
                $q->where('role_id', 3);
            });
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada perangkat terdaftar yang sesuai dengan target.',
            ], 404);
        }

        try {
            $webPush = $this->getWebPushInstance();
            $payload = json_encode([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'icon' => asset('assets/img/pwa/icon-192x192.png'),
                'badge' => asset('assets/img/pwa/icon-96x96.png'),
                'url' => !empty($validated['url']) ? $validated['url'] : url('/premium/katalog'),
                'timestamp' => time(),
            ]);

            $successCount = 0;
            $failedCount = 0;

            foreach ($subscriptions as $subRecord) {
                try {
                    $sub = Subscription::create([
                        'endpoint' => $subRecord->endpoint,
                        'publicKey' => $subRecord->public_key,
                        'authToken' => $subRecord->auth_token,
                        'contentEncoding' => $subRecord->content_encoding ?? 'aes128gcm',
                    ]);
                    $webPush->queueNotification($sub, $payload);
                } catch (\Throwable $e) {
                    $failedCount++;
                }
            }

            try {
                foreach ($webPush->flush() as $report) {
                    if ($report->isSuccess()) {
                        $successCount++;
                    } else {
                        $failedCount++;
                        if ($report->isSubscriptionExpired()) {
                            PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
                        }
                    }
                }
            } catch (\Throwable $flushErr) {
                Log::warning('WebPush Flush warning: ' . $flushErr->getMessage());
                $failedCount++;
            }

            return response()->json([
                'status' => 'success',
                'message' => "Notifikasi berhasil disiarkan: {$successCount} sukses, {$failedCount} gagal/tidak aktif.",
                'success_count' => $successCount,
                'failed_count' => $failedCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('WebPush Broadcast Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyiarkan notifikasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus perangkat terdaftar dari admin panel.
     */
    public function deleteSubscription($id)
    {
        $subscription = PushSubscription::findOrFail($id);
        $subscription->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Perangkat berhasil dihapus dari daftar notifikasi.',
        ]);
    }

    /**
     * Kirim uji notifikasi langsung ke perangkat spesifik berdasarkan ID.
     */
    public function sendTestToDevice($id)
    {
        $subscription = PushSubscription::findOrFail($id);

        try {
            $webPush = $this->getWebPushInstance();

            $sub = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?? 'aes128gcm',
            ]);

            $payload = json_encode([
                'title' => '⚡ Uji Notifikasi Admin Lapaktifikasi',
                'body' => 'Uji notifikasi ke perangkat ' . $subscription->device_name . ' berhasil diterima!',
                'icon' => asset('assets/img/pwa/icon-192x192.png'),
                'badge' => asset('assets/img/pwa/icon-96x96.png'),
                'url' => url('/premium/katalog'),
                'timestamp' => time(),
            ]);

            $report = $webPush->sendOneNotification($sub, $payload);

            if ($report->isSuccess()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Notifikasi uji coba berhasil dikirim ke perangkat ' . $subscription->device_name . '!',
                ]);
            } else {
                if ($report->isSubscriptionExpired()) {
                    $subscription->delete();
                }
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengirim push ke perangkat: ' . $report->getReason(),
                ], 400);
            }
        } catch (\Throwable $e) {
            Log::error('WebPush Test Device Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
}

