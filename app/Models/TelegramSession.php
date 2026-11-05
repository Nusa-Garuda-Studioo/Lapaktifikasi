<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramSession extends Model
{
    use HasFactory;

    protected $table = 'tbl_telegram_sessions';

    protected $fillable = [
        'chat_id',
        'flow',
        'step',
        'payload',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'chat_id' => 'integer',
            'payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}

