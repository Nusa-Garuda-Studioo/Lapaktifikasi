<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessageMap extends Model
{
    use HasFactory;

    protected $table = 'tbl_telegram_message_map';

    protected $fillable = [
        'chat_id',
        'telegram_message_id',
        'laporan_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'chat_id' => 'integer',
            'telegram_message_id' => 'integer',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

