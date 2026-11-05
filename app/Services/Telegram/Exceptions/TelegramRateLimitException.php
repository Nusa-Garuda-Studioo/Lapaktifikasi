<?php

namespace App\Services\Telegram\Exceptions;

class TelegramRateLimitException extends TelegramException
{
    protected int $retryAfter;

    public function __construct(int $retryAfter, string $message = '', int $code = 429, ?\Throwable $previous = null)
    {
        $this->retryAfter = $retryAfter;
        $message = $message ?: "Telegram rate limit reached. Retry after {$retryAfter} seconds.";

        parent::__construct($message, $code, $previous);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

