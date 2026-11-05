<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Toko extends Model
{
    use HasFactory;

    protected $table = 'tbl_toko';
    protected $primaryKey = 'id_toko';

    protected $fillable = [
        'user_id',
        'nama_toko',
        'no_telp',
        'akun_telegram',
        'telegram_chat_id',
        'informasi_toko',
        'logo_toko',
        'komisi_override',
        'saldo',
        'status',
        'telegram_grace_due_at',
        'is_banned',
        'banned_reason',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'telegram_grace_due_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($toko) {
            if (empty($toko->slug)) {
                $toko->slug = static::generateUniqueSlug($toko->nama_toko ?? 'toko');
            }

            // Toko seller baru tidak boleh berstatus aktif tayang sebelum menautkan akun Telegram
            if (!empty($toko->user_id)) {
                $user = User::find($toko->user_id);
                if ($user && !$user->hasTelegram()) {
                    $toko->status = 'nonaktif';
                    if (empty($toko->telegram_grace_due_at)) {
                        $graceDays = SettingWebsite::getCached()?->telegram_seller_grace_days ?? 14;
                        $toko->telegram_grace_due_at = now()->addDays($graceDays);
                    }
                }
            }
        });

        static::updating(function ($toko) {
            if ($toko->isDirty('nama_toko') && empty($toko->slug)) {
                $toko->slug = static::generateUniqueSlug($toko->nama_toko ?? 'toko', $toko->id_toko);
            }
        });
    }

    public static function generateUniqueSlug(string $namaToko, ?int $ignoreId = null): string
    {
        $slug = \Illuminate\Support\Str::slug($namaToko);
        if (empty($slug)) {
            $slug = 'toko-' . \Illuminate\Support\Str::random(6);
        }

        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id_toko', '!=', $ignoreId))
            ->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function produk(): HasMany
    {
        return $this->hasMany(ProdukModel::class, 'id_toko', 'id_toko');
    }

    public function mutasiSaldo(): HasMany
    {
        return $this->hasMany(MutasiSaldo::class, 'id_toko', 'id_toko');
    }

    public function beliProduk(): HasMany
    {
        return $this->hasMany(BeliProdukModel::class, 'id_toko', 'id_toko');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'id_toko', 'id_toko');
    }

    public function badges(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SellerBadge::class, 'tbl_toko_badge', 'id_toko', 'id_badge')
            ->withPivot('diperoleh_pada');
    }

    public function syncRatings()
    {
        $reviews = $this->reviews()->get();
        
        $this->update([
            'jumlah_review' => $reviews->count(),
            'rating_rata_rata' => $reviews->avg('rating') ?? 0,
            'rating_1_star' => $reviews->where('rating', 1)->count(),
            'rating_2_star' => $reviews->where('rating', 2)->count(),
            'rating_3_star' => $reviews->where('rating', 3)->count(),
            'rating_4_star' => $reviews->where('rating', 4)->count(),
            'rating_5_star' => $reviews->where('rating', 5)->count(),
        ]);
    }
}
