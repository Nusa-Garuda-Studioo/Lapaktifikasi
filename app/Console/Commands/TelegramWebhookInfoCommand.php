<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;
use Throwable;

class TelegramWebhookInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display current Telegram bot webhook status and diagnostics';

    /**
     * Execute the console command.
     */
    public function handle(TelegramClient $client): int
    {
        if (!config('services.telegram.enabled')) {
            $this->warn('Warning: TELEGRAM_ENABLED is currently false in configuration.');
        }

        try {
            $info = $client->getWebhookInfo();

            if (!$info) {
                $this->warn('No webhook info returned (Telegram integration may be disabled).');
                return Command::SUCCESS;
            }

            if ($info === true) {
                $this->info('Webhook info retrieved successfully.');
                return Command::SUCCESS;
            }

            if (!is_array($info)) {
                $this->warn('Unexpected webhook info response format.');
                return Command::SUCCESS;
            }

            $rows = [
                ['Webhook URL', !empty($info['url']) ? $info['url'] : '(none)'],
                ['Has Custom Certificate', !empty($info['has_custom_certificate']) ? 'Yes' : 'No'],
                ['Pending Update Count', $info['pending_update_count'] ?? 0],
                ['Max Connections', $info['max_connections'] ?? '(default)'],
                ['Allowed Updates', !empty($info['allowed_updates']) ? implode(', ', $info['allowed_updates']) : '(all)'],
                ['Last Error Date', !empty($info['last_error_date']) ? date('Y-m-d H:i:s', $info['last_error_date']) : '-'],
                ['Last Error Message', $info['last_error_message'] ?? '-'],
                ['Last Sync Error Date', !empty($info['last_synchronization_error_date']) ? date('Y-m-d H:i:s', $info['last_synchronization_error_date']) : '-'],
            ];

            $this->table(['Property', 'Value'], $rows);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Failed to get webhook info: ' . $client->sanitize($e->getMessage()));
            return Command::FAILURE;
        }
    }
}

