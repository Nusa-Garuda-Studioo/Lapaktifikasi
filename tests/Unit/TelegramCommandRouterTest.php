<?php

namespace Tests\Unit;

use App\Services\Telegram\CommandRouter;
use App\Services\Telegram\TelegramClient;
use Mockery;
use PHPUnit\Framework\TestCase;

class TelegramCommandRouterTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_start_command_without_token_replies_linking_instructions(): void
    {
        $called = false;
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with(123456, Mockery::on(function ($text) use (&$called) {
                $called = str_contains($text, 'menautkan akun') && str_contains($text, 'Lapaktifikasi');
                return $called;
            }))
            ->andReturn(['ok' => true]);

        $router = new CommandRouter($mockClient);
        $router->handle([
            'chat' => ['id' => 123456, 'type' => 'private'],
            'text' => '/start',
        ]);

        $this->assertTrue($called);
    }

    public function test_bantuan_command_replies_command_list(): void
    {
        $called = false;
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with(123456, Mockery::on(function ($text) use (&$called) {
                $called = str_contains($text, 'Daftar Perintah')
                    && str_contains($text, '/start')
                    && str_contains($text, '/bantuan');
                return $called;
            }))
            ->andReturn(['ok' => true]);

        $router = new CommandRouter($mockClient);
        $router->handle([
            'chat' => ['id' => 123456, 'type' => 'private'],
            'text' => '/bantuan',
        ]);

        $this->assertTrue($called);
    }

    public function test_bantuan_command_with_bot_username_suffix(): void
    {
        $called = false;
        $mockClient = Mockery::mock(TelegramClient::class);
        $mockClient->shouldReceive('sendMessage')
            ->once()
            ->with(987654, Mockery::on(function ($text) use (&$called) {
                $called = str_contains($text, 'Daftar Perintah');
                return $called;
            }))
            ->andReturn(['ok' => true]);

        $router = new CommandRouter($mockClient);
        $router->handle([
            'chat' => ['id' => 987654, 'type' => 'supergroup'],
            'text' => '/bantuan@lapaktifikasi_bot',
        ]);

        $this->assertTrue($called);
    }
}
