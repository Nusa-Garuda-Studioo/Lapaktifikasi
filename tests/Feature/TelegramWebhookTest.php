<?php

namespace Tests\Feature;

use App\Jobs\ProcessTelegramUpdate;
use App\Models\TelegramUpdate;
use App\Services\Telegram\TelegramClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    protected string $testSecret = 'test_webhook_secret_key_12345';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.telegram.enabled', true);
        Config::set('services.telegram.webhook_secret', $this->testSecret);
    }

    protected function tearDown(): void
    {
        TelegramUpdate::whereIn('update_id', [10001, 10002, 10003])->delete();
        Mockery::close();
        parent::tearDown();
    }

    public function test_webhook_rejects_missing_secret_token(): void
    {
        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 10001,
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 12345],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_rejects_invalid_secret_token(): void
    {
        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 10001,
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 12345],
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong_secret_token',
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_accepts_valid_secret_and_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 10001,
            'message' => [
                'text' => '/bantuan',
                'chat' => ['id' => 12345],
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => $this->testSecret,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessTelegramUpdate::class, function ($job) {
            return ($job->updatePayload['update_id'] ?? null) === 10001;
        });

        $this->assertDatabaseHas('tbl_telegram_updates', [
            'update_id' => 10001,
        ]);
    }

    public function test_duplicate_update_id_is_only_processed_once(): void
    {
        Queue::fake();

        $payload = [
            'update_id' => 10002,
            'message' => [
                'text' => '/bantuan',
                'chat' => ['id' => 12345],
            ],
        ];

        $headers = [
            'X-Telegram-Bot-Api-Secret-Token' => $this->testSecret,
        ];

        // First attempt: should succeed and queue job
        $firstResponse = $this->postJson('/api/telegram/webhook', $payload, $headers);
        $firstResponse->assertStatus(200);
        $firstResponse->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessTelegramUpdate::class, 1);

        // Second attempt with same update_id: should return 200 without dispatching second job
        $secondResponse = $this->postJson('/api/telegram/webhook', $payload, $headers);
        $secondResponse->assertStatus(200);
        $secondResponse->assertJson(['status' => 'already_processed']);

        // Assert job was NOT pushed again (count remains 1)
        Queue::assertPushed(ProcessTelegramUpdate::class, 1);
    }

    public function test_process_telegram_update_job_handles_bantuan_command_and_marks_processed(): void
    {
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with(12345, Mockery::on(function ($text) {
                return str_contains($text, 'Daftar Perintah');
            }))
            ->andReturn(['ok' => true]);

        $this->app->instance(TelegramClient::class, $mockClient);

        $updateRecord = TelegramUpdate::create([
            'update_id' => 10003,
            'received_at' => now(),
        ]);

        $this->assertNull($updateRecord->processed_at);

        $job = new ProcessTelegramUpdate([
            'update_id' => 10003,
            'message' => [
                'text' => '/bantuan',
                'chat' => ['id' => 12345, 'type' => 'private'],
            ],
        ], $updateRecord->id);

        $job->handle(
            $mockClient,
            $this->app->make(\App\Services\Telegram\CommandRouter::class),
            $this->app->make(\App\Services\Telegram\FlowHandler::class),
            $this->app->make(\App\Services\Telegram\CallbackRouter::class),
            $this->app->make(\App\Services\Telegram\MembershipHandler::class)
        );

        $updateRecord->refresh();
        $this->assertNotNull($updateRecord->processed_at);
    }
}

