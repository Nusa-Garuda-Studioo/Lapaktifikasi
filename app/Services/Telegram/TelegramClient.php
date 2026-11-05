<?php

namespace App\Services\Telegram;

use App\Services\Telegram\Exceptions\TelegramChatBlockedException;
use App\Services\Telegram\Exceptions\TelegramException;
use App\Services\Telegram\Exceptions\TelegramRateLimitException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Throwable;

class TelegramClient
{
    protected ClientInterface $client;
    protected ?string $botToken;
    protected bool $enabled;
    protected string $baseUri = 'https://api.telegram.org/';

    public function __construct(
        ?ClientInterface $client = null,
        ?string $botToken = null,
        ?bool $enabled = null
    ) {
        $this->botToken = $botToken ?? config('services.telegram.bot_token');
        $this->enabled = $enabled ?? (bool) config('services.telegram.enabled', false);

        $this->client = $client ?? new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 15.0,
            'http_errors' => true,
        ]);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->botToken);
    }

    public static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function sendMessage(int|string $chatId, string $text, array $options = []): array|bool|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options);

        return $this->request('sendMessage', $params);
    }

    public function editMessageText(int|string $chatId, int $messageId, string $text, array $options = []): array|bool|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $params = array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options);

        return $this->request('editMessageText', $params);
    }

    public function answerCallbackQuery(string $callbackQueryId, array $options = []): array|bool|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $params = array_merge([
            'callback_query_id' => $callbackQueryId,
        ], $options);

        return $this->request('answerCallbackQuery', $params);
    }

    public function sendPhoto(int|string $chatId, mixed $photo, array $options = []): array|bool|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        // If photo is a local file path that exists, send as multipart
        if (is_string($photo) && file_exists($photo) && is_file($photo)) {
            $multipart = [
                [
                    'name' => 'chat_id',
                    'contents' => (string) $chatId,
                ],
                [
                    'name' => 'photo',
                    'contents' => fopen($photo, 'r'),
                    'filename' => basename($photo),
                ],
            ];

            if (!isset($options['parse_mode']) && isset($options['caption'])) {
                $options['parse_mode'] = 'HTML';
            }

            foreach ($options as $key => $value) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => is_array($value) ? json_encode($value) : (string) $value,
                ];
            }

            return $this->requestMultipart('sendPhoto', $multipart);
        }

        // Otherwise (file_id or remote URL) send as standard JSON
        $params = array_merge([
            'chat_id' => $chatId,
            'photo' => $photo,
        ], $options);

        if (isset($params['caption']) && !isset($params['parse_mode'])) {
            $params['parse_mode'] = 'HTML';
        }

        return $this->request('sendPhoto', $params);
    }

    public function getFile(string $fileId): array|bool|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return $this->request('getFile', ['file_id' => $fileId]);
    }

    public function downloadFile(string $filePath, ?string $destination = null): string|bool
    {
        if (!$this->isEnabled()) {
            return $destination ? false : '';
        }

        $uri = "/file/bot{$this->botToken}/" . ltrim($filePath, '/');

        try {
            $options = [];
            if ($destination !== null) {
                $options['sink'] = $destination;
            }

            $response = $this->client->request('GET', $uri, $options);

            if ($destination !== null) {
                return true;
            }

            return (string) $response->getBody();
        } catch (ClientException | ServerException $e) {
            $this->handleHttpException($e);
        } catch (Throwable $e) {
            throw new TelegramException($this->sanitize($e->getMessage()), (int) $e->getCode());
        }

        return false;
    }

    public function createForumTopic(int|string $chatId, string $name, array $options = []): array|bool|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $params = array_merge([
            'chat_id' => $chatId,
            'name' => $name,
        ], $options);

        return $this->request('createForumTopic', $params);
    }

    public function setWebhook(string $url, ?string $secretToken = null, array $allowedUpdates = []): array|bool|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $params = ['url' => $url];

        if ($secretToken !== null && $secretToken !== '') {
            $params['secret_token'] = $secretToken;
        }

        if (!empty($allowedUpdates)) {
            $params['allowed_updates'] = $allowedUpdates;
        }

        return $this->request('setWebhook', $params);
    }

    public function deleteWebhook(bool $dropPendingUpdates = false): array|bool|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return $this->request('deleteWebhook', [
            'drop_pending_updates' => $dropPendingUpdates,
        ]);
    }

    public function getWebhookInfo(): array|bool|null
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return $this->request('getWebhookInfo');
    }

    protected function request(string $method, array $params = []): array|bool|null
    {
        $uri = "/bot{$this->botToken}/{$method}";

        try {
            $response = $this->client->request('POST', $uri, [
                'json' => $params,
            ]);

            return $this->parseResponse($response);
        } catch (ClientException | ServerException $e) {
            $this->handleHttpException($e);
        } catch (Throwable $e) {
            if ($e instanceof TelegramException) {
                throw $e;
            }
            throw new TelegramException($this->sanitize($e->getMessage()), (int) $e->getCode());
        }

        return null;
    }

    protected function requestMultipart(string $method, array $multipart): array|bool|null
    {
        $uri = "/bot{$this->botToken}/{$method}";

        try {
            $response = $this->client->request('POST', $uri, [
                'multipart' => $multipart,
            ]);

            return $this->parseResponse($response);
        } catch (ClientException | ServerException $e) {
            $this->handleHttpException($e);
        } catch (Throwable $e) {
            if ($e instanceof TelegramException) {
                throw $e;
            }
            throw new TelegramException($this->sanitize($e->getMessage()), (int) $e->getCode());
        }

        return null;
    }

    protected function parseResponse($response): array|bool|null
    {
        $contents = (string) $response->getBody();
        $data = json_decode($contents, true);

        if (!is_array($data) || !($data['ok'] ?? false)) {
            $desc = $data['description'] ?? 'Telegram API error';
            throw new TelegramException($this->sanitize($desc));
        }

        if (array_key_exists('result', $data)) {
            return $data['result'];
        }

        return $data;
    }

    protected function handleHttpException(ClientException|ServerException $e): void
    {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 0;
        $body = [];

        if ($response) {
            $rawBody = (string) $response->getBody();
            $body = json_decode($rawBody, true) ?: [];
        }

        if ($statusCode === 429) {
            $retryAfter = (int) ($body['parameters']['retry_after'] ?? 5);
            throw new TelegramRateLimitException(
                $retryAfter,
                "Telegram rate limited. Retry after {$retryAfter} seconds."
            );
        }

        if ($statusCode === 403) {
            $desc = $body['description'] ?? 'Forbidden: bot was blocked or chat deleted';
            throw new TelegramChatBlockedException($this->sanitize($desc), 403);
        }

        $desc = $body['description'] ?? 'Telegram request failed';
        throw new TelegramException(
            $this->sanitize("Telegram API error [{$statusCode}]: {$desc}"),
            $statusCode
        );
    }

    public function sanitize(string $text): string
    {
        if (empty($this->botToken)) {
            return $text;
        }

        return str_replace($this->botToken, '[REDACTED]', $text);
    }
}
