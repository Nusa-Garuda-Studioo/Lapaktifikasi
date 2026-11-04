<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_berita';

    protected $fillable = [
        'judul',
        'slug',
        'subjudul',
        'konten',
        'gambar',
        'admin_id',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke User (Admin / Penulis Berita)
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Scope untuk memfilter berita yang sudah dipublikasikan
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Accessor untuk mendapatkan URL lengkap gambar berita
     */
    public function getGambarUrlAttribute(): ?string
    {
        if (empty($this->gambar)) {
            return null;
        }

        if (str_starts_with($this->gambar, 'http://') || str_starts_with($this->gambar, 'https://')) {
            return $this->gambar;
        }

        return asset('storage/' . ltrim($this->gambar, '/'));
    }

    /**
     * Sanitasi konten HTML untuk mencegah serangan XSS.
     */
    public static function cleanHtml(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Izinkan tag teks dan tata letak aman yang sering digunakan di editor artikel
        $allowedTags = '<p><br><b><strong><i><em><u><s><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><img><table><thead><tbody><tr><th><td><hr>';
        $cleaned = strip_tags($html, $allowedTags);

        // Hapus inline event handlers berbahaya seperti onerror, onclick, onload, onmouseover, dsb.
        $cleaned = preg_replace('/\s+on[a-zA-Z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $cleaned);

        // Netralkan skema URI berbahaya seperti javascript: atau vbscript: pada tautan atau media
        $cleaned = preg_replace('/(href|src)\s*=\s*["\']\s*(javascript|vbscript|data(?!\s*:\s*image)):[^"\']*["\']/i', '$1="#"', $cleaned);

        return $cleaned;
    }

    /**
     * Mutator untuk memastikan konten berita selalu bersih saat disimpan
     */
    public function setKontenAttribute(?string $value): void
    {
        $this->attributes['konten'] = self::cleanHtml($value);
    }

    /**
     * Accessor untuk mendapatkan konten berita yang sudah disanitasi
     */
    public function getCleanKontenAttribute(): string
    {
        return self::cleanHtml($this->konten);
    }
}
