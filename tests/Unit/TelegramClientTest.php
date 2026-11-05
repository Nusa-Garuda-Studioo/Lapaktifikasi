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
        $this->assertNull($client->deleteWebhook());
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

    public function test_telegram_client_parses_boolean_true_result(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'ok' => true,
                'result' => true,
                'description' => 'Webhook was set',
            ])),
            new Response(200, [], json_encode([
                'ok' => true,
                'result' => true,
            ])),
            new Response(200, [], json_encode([
                'ok' => true,
                'result' => true,
            ])),
            new Response(200, [], json_encode([
                'ok' => true,
                'result' => true,
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);
        $client = new TelegramClient($guzzle, 'test_token', true);

        // 1. setWebhook returns boolean true
        $resSetWebhook = $client->setWebhook('https://example.com/api/telegram/webhook');
        $this->assertTrue($resSetWebhook);

        // 2. deleteWebhook returns boolean true
        $resDeleteWebhook = $client->deleteWebhook();
        $this->assertTrue($resDeleteWebhook);

        // 3. answerCallbackQuery returns boolean true
        $resAnswer = $client->answerCallbackQuery('cb_query_123');
        $this->assertTrue($resAnswer);

        // 4. editMessageText returns boolean true
        $resEdit = $client->editMessageText(12345, 678, 'Updated text');
        $this->assertTrue($resEdit);
    }

    public function test_telegram_client_parses_array_result(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'ok' => true,
                'result' => [
                    'message_id' => 999,
                    'text' => 'Hello World',
                    'chat' => ['id' => 12345],
                ],
            ])),
            new Response(200, [], json_encode([
                'ok' => true,
                'result' => [
                    'url' => 'https://example.com/webhook',
                    'pending_update_count' => 0,
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);
        $client = new TelegramClient($guzzle, 'test_token', true);

        // 1. sendMessage returns array
        $resMessage = $client->sendMessage(12345, 'Hello World');
        $this->assertIsArray($resMessage);
        $this->assertSame(999, $resMessage['message_id']);
        $this->assertSame('Hello World', $resMessage['text']);

        // 2. getWebhookInfo returns array
        $resWebhookInfo = $client->getWebhookInfo();
        $this->assertIsArray($resWebhookInfo);
        $this->assertSame('https://example.com/webhook', $resWebhookInfo['url']);
    }

    public function test_telegram_client_throws_exception_when_ok_is_false(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'ok' => false,
                'error_code' => 400,
                'description' => 'Bad Request: bad webhook: IP is reserved',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);
        $client = new TelegramClient($guzzle, 'test_token', true);

        $this->expectException(TelegramException::class);
        $this->expectExceptionMessage('Bad Request: bad webhook: IP is reserved');

        $client->setWebhook('https://127.0.0.1/webhook');
    }
}

