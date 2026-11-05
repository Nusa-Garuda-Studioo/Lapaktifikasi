<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanLog extends Model
{
    use HasFactory;

    protected $table = 'tbl_laporan_log';
    protected $primaryKey = 'id_log';

    protected $fillable = [
        'laporan_id',
        'user_id',
        'status_lama',
        'status_baru',
        'sumber_perubahan',
        'keterangan',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

