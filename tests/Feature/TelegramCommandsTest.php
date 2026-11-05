<?php

namespace Tests\Feature;

use App\Services\Telegram\TelegramClient;
use Mockery;
use Tests\TestCase;

class TelegramCommandsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_telegram_set_webhook_command_registers_webhook(): void
    {
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('setWebhook')
            ->once()
            ->with(
                'https://mytestdomain.com/api/telegram/webhook',
                Mockery::any(),
                ['message', 'callback_query', 'my_chat_member']
            )
            ->andReturn(['ok' => true]);

        $this->app->instance(TelegramClient::class, $mockClient);

        $this->artisan('telegram:set-webhook', [
            '--url' => 'https://mytestdomain.com/api/telegram/webhook',
        ])
            ->expectsOutputToContain('Registering Telegram webhook: https://mytestdomain.com/api/telegram/webhook')
            ->expectsOutputToContain('Telegram webhook successfully registered!')
            ->assertSuccessful();
    }

    public function test_telegram_webhook_info_command_displays_table(): void
    {
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('getWebhookInfo')
            ->once()
            ->andReturn([
                'url' => 'https://mytestdomain.com/api/telegram/webhook',
                'has_custom_certificate' => false,
                'pending_update_count' => 3,
                'max_connections' => 40,
                'allowed_updates' => ['message', 'callback_query'],
                'last_error_date' => null,
                'last_error_message' => null,
            ]);

        $this->app->instance(TelegramClient::class, $mockClient);

        $this->artisan('telegram:webhook-info')
            ->expectsTable(
                ['Property', 'Value'],
                [
                    ['Webhook URL', 'https://mytestdomain.com/api/telegram/webhook'],
                    ['Has Custom Certificate', 'No'],
                    ['Pending Update Count', 3],
                    ['Max Connections', 40],
                    ['Allowed Updates', 'message, callback_query'],
                    ['Last Error Date', '-'],
                    ['Last Error Message', '-'],
                    ['Last Sync Error Date', '-'],
                ]
            )
            ->assertSuccessful();
    }
}

