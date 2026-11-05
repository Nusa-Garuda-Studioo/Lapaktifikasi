<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiLog extends Model
{
    use HasFactory;

    protected $table = 'tbl_notifikasi_log';

    protected $fillable = [
        'user_id',
        'event',
        'channel',
        'status',
        'error',
        'laporan_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }
}

