<?php

namespace App\Services\Telegram;

use App\Models\User;

class MembershipHandler
{
    protected TelegramClient $client;

    public function __construct(TelegramClient $client)
    {
        $this->client = $client;
    }

    /**
     * Handle my_chat_member updates (bot blocked/kicked or unblocked/member).
     */
    public function handle(array $myChatMember): void
    {
        $chatId = $myChatMember['chat']['id'] ?? ($myChatMember['from']['id'] ?? null);
        $newStatus = $myChatMember['new_chat_member']['status'] ?? null;

        if (!$chatId || !$newStatus) {
            return;
        }

        if ($newStatus === 'kicked') {
            User::where('telegram_chat_id', (int) $chatId)->update([
                'telegram_blocked_at' => now(),
            ]);
        } elseif ($newStatus === 'member') {
            User::where('telegram_chat_id', (int) $chatId)->update([
                'telegram_blocked_at' => null,
            ]);
        }
    }
}
