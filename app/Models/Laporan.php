<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laporan extends Model
{
    use HasFactory;

    protected $table = 'tbl_laporan';

    protected $fillable = [
        'user_id',
        'assigned_to',
        'judul',
        'deskripsi',
        'gambar',
        'status',
        'first_response_at',
        'sla_due_at',
        'sla_reminder_sent_at',
        'escalated_at',
        'telegram_topic_id',
    ];

    protected function casts(): array
    {
        return [
            'first_response_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'sla_reminder_sent_at' => 'datetime',
            'escalated_at' => 'datetime',
            'telegram_topic_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function pesan(): HasMany
    {
        return $this->hasMany(LaporanPesan::class, 'laporan_id');
    }

    public function messageMaps(): HasMany
    {
        return $this->hasMany(TelegramMessageMap::class, 'laporan_id');
    }

    public function notifikasiLogs(): HasMany
    {
        return $this->hasMany(NotifikasiLog::class, 'laporan_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LaporanLog::class, 'laporan_id', 'id');
    }
}
