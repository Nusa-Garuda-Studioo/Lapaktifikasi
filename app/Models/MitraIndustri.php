<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MitraIndustri extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'image_path', 'is_active'];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('landing_mitras');
        });

        static::deleted(function () {
            Cache::forget('landing_mitras');
        });
    }
}
