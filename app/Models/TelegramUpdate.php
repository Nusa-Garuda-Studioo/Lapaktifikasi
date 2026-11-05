<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUpdate extends Model
{
    use HasFactory;

    protected $table = 'tbl_telegram_updates';

    protected $fillable = [
        'update_id',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'update_id' => 'integer',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}

