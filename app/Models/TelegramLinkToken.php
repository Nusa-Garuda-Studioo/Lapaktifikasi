<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLinkToken extends Model
{
    use HasFactory;

    protected $table = 'tbl_telegram_link_tokens';

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    /**
     * Sembunyikan token_hash agar tidak bocor di serialisasi API / JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function isValid(): bool
    {
        return !$this->isUsed() && !$this->isExpired();
    }
}

