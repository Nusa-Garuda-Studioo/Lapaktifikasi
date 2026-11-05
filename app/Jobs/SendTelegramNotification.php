<?php

namespace App\Jobs;

use App\Models\NotifikasiLog;
use App\Models\TelegramMessageMap;
use App\Models\User;
use App\Services\FonnteService;
use App\Services\Telegram\Exceptions\TelegramChatBlockedException;
use App\Services\Telegram\Exceptions\TelegramRateLimitException;
use App\Services\Telegram\TelegramClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int|string $chatId,
        public string $text,
        public array $replyMarkup = [],
        public ?int $laporanId = null,
        public ?int $userId = null,
        public string $event = 'laporan_notification',
        public ?int $logId = null,
        public array $fallbackContext = [],
        public ?int $messageThreadId = null
    ) {
    }

    public function backoff(): array
    {
        return [5, 15, 60];
    }

    public function handle(TelegramClient $client, FonnteService $fonnte): void
    {
        if (!config('services.telegram.enabled')) {
            $this->updateLog('skipped', 'Telegram disabled (TELEGRAM_ENABLED=false)');
            $this->executeFallback($fonnte);
            return;
        }

        try {
            $options = [];
            if (!empty($this->replyMarkup)) {
                $options['reply_markup'] = $this->replyMarkup;
            }
            if ($this->messageThreadId) {
                $options['message_thread_id'] = $this->messageThreadId;
            }

            $response = $client->sendMessage($this->chatId, $this->text, $options);

            $messageId = is_array($response) ? ($response['result']['message_id'] ?? $response['message_id'] ?? null) : null;

            // Simpan mapping jika ada laporanId dan messageId
            if ($messageId && $this->laporanId) {
                try {
                    TelegramMessageMap::updateOrCreate(
                        [
                            'chat_id' => (int) $this->chatId,
                            'telegram_message_id' => (int) $messageId,
                        ],
                        [
                            'laporan_id' => $this->laporanId,
                            'user_id' => $this->userId,
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning('SendTelegramNotification: Gagal menyimpan TelegramMessageMap: ' . $e->getMessage());
                }
            }

            $this->updateLog('sent');
        } catch (TelegramRateLimitException $e) {
            if ($this->attempts() < $this->tries) {
                $retryAfter = $e->getRetryAfter() ?: 5;
                $this->release($retryAfter);
                return;
            }

            $this->updateLog('failed', 'Rate limit 429 berulang: ' . $e->getMessage());
            $this->executeFallback($fonnte);
        } catch (TelegramChatBlockedException $e) {
            // User memblokir bot (403)
            if ($this->userId) {
                User::where('id', $this->userId)->update([
                    'telegram_blocked_at' => now(),
                ]);
            }

            $this->updateLog('failed', 'Bot diblokir oleh penerima (403)');
            $this->executeFallback($fonnte);
        } catch (\Throwable $e) {
            $this->updateLog('failed', 'Gagal mengirim telegram: ' . $e->getMessage());
            $this->executeFallback($fonnte);
        }
    }

    protected function updateLog(string $status, ?string $error = null): void
    {
        if ($this->logId) {
            NotifikasiLog::where('id', $this->logId)->update([
                'status' => $status,
                'error' => $error,
                'sent_at' => now(),
            ]);
        }
    }

    protected function executeFallback(FonnteService $fonnte): void
    {
        if (empty($this->fallbackContext)) {
            return;
        }

        $phone = $this->fallbackContext['phone'] ?? null;
        $message = $this->fallbackContext['message'] ?? null;

        if (!$phone || !$message) {
            return;
        }

        // Catat log pengiriman WhatsApp fallback
        $waLog = NotifikasiLog::create([
            'user_id' => $this->userId,
            'event' => $this->event,
            'channel' => 'whatsapp',
            'status' => 'pending',
            'error' => null,
            'laporan_id' => $this->laporanId,
            'sent_at' => now(),
        ]);

        try {
            $resp = $fonnte->sendText($phone, $message);
            $success = ($resp['status'] ?? false) === true;

            $waLog->update([
                'status' => $success ? 'sent' : 'failed',
                'error' => $success ? null : ($resp['reason'] ?? 'Gagal mengirim pesan WhatsApp'),
            ]);
        } catch (\Throwable $e) {
            $waLog->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
