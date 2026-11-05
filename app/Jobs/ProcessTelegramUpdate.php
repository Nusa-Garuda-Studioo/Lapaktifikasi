<?php

namespace App\Jobs;

use App\Models\TelegramUpdate;
use App\Services\Telegram\CallbackRouter;
use App\Services\Telegram\CommandRouter;
use App\Services\Telegram\Exceptions\TelegramChatBlockedException;
use App\Services\Telegram\Exceptions\TelegramRateLimitException;
use App\Services\Telegram\FlowHandler;
use App\Services\Telegram\MembershipHandler;
use App\Services\Telegram\TelegramClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTelegramUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public array $updatePayload,
        public ?int $updateRecordId = null
    ) {
    }

    public function handle(
        TelegramClient $client,
        CommandRouter $commandRouter,
        FlowHandler $flowHandler,
        CallbackRouter $callbackRouter,
        MembershipHandler $membershipHandler
    ): void {
        if (!config('services.telegram.enabled')) {
            $this->markAsProcessed();
            return;
        }

        try {
            if (isset($this->updatePayload['message'])) {
                $message = $this->updatePayload['message'];
                $text = trim($message['text'] ?? '');

                if ($this->isCommand($message, $text)) {
                    $commandRouter->handle($message);
                } else {
                    $flowHandler->handle($message);
                }
            } elseif (isset($this->updatePayload['callback_query'])) {
                $callbackRouter->handle($this->updatePayload['callback_query']);
            } elseif (isset($this->updatePayload['my_chat_member'])) {
                $membershipHandler->handle($this->updatePayload['my_chat_member']);
            }

            $this->markAsProcessed();
        } catch (TelegramRateLimitException $e) {
            $this->release($e->getRetryAfter());
        } catch (TelegramChatBlockedException $e) {
            Log::info('ProcessTelegramUpdate: Bot was blocked or chat was deleted by user.');
            $this->markAsProcessed();
        }
    }

    protected function isCommand(array $message, string $text): bool
    {
        if (str_starts_with($text, '/')) {
            return true;
        }

        if (!empty($message['entities'])) {
            foreach ($message['entities'] as $entity) {
                if (($entity['type'] ?? '') === 'bot_command' && ($entity['offset'] ?? -1) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function markAsProcessed(): void
    {
        if ($this->updateRecordId) {
            TelegramUpdate::where('id', $this->updateRecordId)->update([
                'processed_at' => now(),
            ]);
        } elseif (!empty($this->updatePayload['update_id'])) {
            TelegramUpdate::where('update_id', $this->updatePayload['update_id'])->update([
                'processed_at' => now(),
            ]);
        }
    }
}

