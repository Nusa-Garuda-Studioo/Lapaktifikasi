<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SettingKomisi extends Model
{
    use HasFactory;

    protected $table = 'tbl_setting_komisi';

    const CREATED_AT = null;

    protected $fillable = [
        'komisi_default',
        'digital_file_limit_mb',
        'is_maintenance',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('setting_komisi_global');
        });

        static::deleted(function () {
            Cache::forget('setting_komisi_global');
        });
    }
}
