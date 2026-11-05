<?php

namespace Tests\Unit;

use App\Services\Telegram\Exceptions\TelegramChatBlockedException;
use App\Services\Telegram\Exceptions\TelegramException;
use App\Services\Telegram\Exceptions\TelegramRateLimitException;
use App\Services\Telegram\TelegramClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class TelegramClientTest extends TestCase
{
    public function test_telegram_client_no_op_when_disabled(): void
    {
        $mock = new MockHandler();
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $client = new TelegramClient($guzzle, 'test_token', false);

        $this->assertFalse($client->isEnabled());
        $this->assertNull($client->sendMessage(12345, 'Test message'));
        $this->assertNull($client->editMessageText(12345, 1, 'Edit'));
        $this->assertNull($client->answerCallbackQuery('cb_123'));
        $this->assertNull($client->sendPhoto(12345, 'photo_id'));
        $this->assertNull($client->getFile('file_id'));
        $this->assertSame('', $client->downloadFile('path/file.jpg'));
        $this->assertNull($client->createForumTopic(12345, 'Topic'));
        $this->assertNull($client->setWebhook('https://example.com'));
        $this->assertNull($client->getWebhookInfo());

        // Assert no HTTP requests were made to Guzzle
        $this->assertSame(0, $mock->count());
    }

    public function test_telegram_client_handles_403_and_throws_chat_blocked_exception(): void
    {
        $mock = new MockHandler([
            new Response(403, [], json_encode([
                'ok' => false,
                'error_code' => 403,
                'description' => 'Forbidden: bot was blocked by the user',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);
        $client = new TelegramClient($guzzle, 'my_secret_token_123', true);

        $this->expectException(TelegramChatBlockedException::class);
        $this->expectExceptionMessage('Forbidden: bot was blocked by the user');

        $client->sendMessage(12345, 'Hello');
    }

    public function test_telegram_client_handles_429_and_throws_rate_limit_exception_with_retry_after(): void
    {
        $mock = new MockHandler([
            new Response(429, [], json_encode([
                'ok' => false,
                'error_code' => 429,
                'description' => 'Too Many Requests: retry after 25',
                'parameters' => [
                    'retry_after' => 25,
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);
        $client = new TelegramClient($guzzle, 'my_secret_token_123', true);

        try {
            $client->sendMessage(12345, 'Hello');
            $this->fail('Expected TelegramRateLimitException was not thrown.');
        } catch (TelegramRateLimitException $e) {
            $this->assertSame(25, $e->getRetryAfter());
            $this->assertStringContainsString('25', $e->getMessage());
        }
    }

    public function test_telegram_client_sanitizes_token_from_exception_message(): void
    {
        $secretToken = '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11';

        $mock = new MockHandler([
            new Response(500, [], json_encode([
                'ok' => false,
                'error_code' => 500,
                'description' => "Internal server error involving token {$secretToken}",
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);
        $client = new TelegramClient($guzzle, $secretToken, true);

        try {
            $client->sendMessage(12345, 'Hello');
            $this->fail('Expected TelegramException was not thrown.');
        } catch (TelegramException $e) {
            $this->assertStringNotContainsString($secretToken, $e->getMessage());
            $this->assertStringContainsString('[REDACTED]', $e->getMessage());
        }
    }

    public function test_escape_helper_escapes_html(): void
    {
        $input = 'Toko <Lapaktifikasi> & "Produk" \'Digital\'';
        $escaped = TelegramClient::escape($input);

        $this->assertSame('Toko &lt;Lapaktifikasi&gt; &amp; &quot;Produk&quot; &#039;Digital&#039;', $escaped);
    }
}

