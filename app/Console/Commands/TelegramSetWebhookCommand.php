<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;
use Throwable;

class TelegramSetWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {--url= : Custom webhook URL to register}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register the Telegram bot webhook with secret token and allowed updates';

    /**
     * Execute the console command.
     */
    public function handle(TelegramClient $client): int
    {
        if (!config('services.telegram.enabled')) {
            $this->warn('Warning: TELEGRAM_ENABLED is currently false in configuration.');
        }

        $appUrl = (string) config('app.url', 'http://localhost');
        $webhookUrl = $this->option('url') ?: (rtrim($appUrl, '/') . '/api/telegram/webhook');
        $secretToken = (string) config('services.telegram.webhook_secret', '');
        $allowedUpdates = ['message', 'callback_query', 'my_chat_member'];

        $this->info("Registering Telegram webhook: {$webhookUrl}");
        $this->line("Allowed updates: " . implode(', ', $allowedUpdates));

        try {
            $result = $client->setWebhook($webhookUrl, $secretToken, $allowedUpdates);

            if ($result) {
                $this->info('Telegram webhook successfully registered!');
                return Command::SUCCESS;
            }

            $this->warn('Webhook call returned empty response (possibly disabled).');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Failed to set webhook: ' . $client->sanitize($e->getMessage()));
            return Command::FAILURE;
        }
    }
}

