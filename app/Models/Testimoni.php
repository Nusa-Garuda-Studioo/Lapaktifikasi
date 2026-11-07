<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Testimoni extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'rating',
        'comment',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('landing_testimonis');
        });

        static::deleted(function () {
            Cache::forget('landing_testimonis');
        });
    }
}
