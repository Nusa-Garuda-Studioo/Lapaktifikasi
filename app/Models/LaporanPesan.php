<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPesan extends Model
{
    use HasFactory;

    protected $table = 'tbl_laporan_pesan';

    protected $fillable = [
        'laporan_id',
        'sender_user_id',
        'sender_role',
        'body',
        'sumber',
        'lampiran',
        'telegram_message_id',
    ];

    protected function casts(): array
    {
        return [
            'telegram_message_id' => 'integer',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}

