<?php

namespace Tests\Feature;

use App\Services\Telegram\Exceptions\TelegramException;
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

    public function test_telegram_set_webhook_command_succeeds_with_boolean_true_result(): void
    {
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('setWebhook')
            ->once()
            ->andReturn(true);

        $this->app->instance(TelegramClient::class, $mockClient);

        $this->artisan('telegram:set-webhook')
            ->expectsOutputToContain('Telegram webhook successfully registered!')
            ->assertSuccessful();
    }

    public function test_telegram_set_webhook_command_succeeds_with_array_result_and_description(): void
    {
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('setWebhook')
            ->once()
            ->andReturn([
                'ok' => true,
                'result' => true,
                'description' => 'Webhook was set',
            ]);

        $this->app->instance(TelegramClient::class, $mockClient);

        $this->artisan('telegram:set-webhook')
            ->expectsOutputToContain('Telegram webhook successfully registered!')
            ->expectsOutputToContain('Webhook was set')
            ->assertSuccessful();
    }

    public function test_telegram_set_webhook_command_prints_description_and_fails_when_ok_is_false(): void
    {
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sanitize')
            ->andReturnUsing(fn($text) => $text);
        $mockClient->shouldReceive('setWebhook')
            ->once()
            ->andReturn([
                'ok' => false,
                'description' => 'Bad Request: bad webhook: IP is reserved',
            ]);

        $this->app->instance(TelegramClient::class, $mockClient);

        $this->artisan('telegram:set-webhook')
            ->expectsOutputToContain('Failed to set webhook: Bad Request: bad webhook: IP is reserved')
            ->assertFailed();
    }

    public function test_telegram_set_webhook_command_prints_description_and_fails_on_exception(): void
    {
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sanitize')
            ->andReturnUsing(fn($text) => $text);
        $mockClient->shouldReceive('setWebhook')
            ->once()
            ->andThrow(new TelegramException('Bad Request: HTTPS url must be provided'));

        $this->app->instance(TelegramClient::class, $mockClient);

        $this->artisan('telegram:set-webhook')
            ->expectsOutputToContain('Failed to set webhook: Bad Request: HTTPS url must be provided')
            ->assertFailed();
    }

    public function test_telegram_webhook_info_command_handles_boolean_true(): void
    {
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('getWebhookInfo')
            ->once()
            ->andReturn(true);

        $this->app->instance(TelegramClient::class, $mockClient);

        $this->artisan('telegram:webhook-info')
            ->expectsOutputToContain('Webhook info retrieved successfully.')
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

