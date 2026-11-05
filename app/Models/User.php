<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\Role;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture',
        'role_id',
        'is_banned',
        'banned_reason',
        'telegram_chat_id',
        'telegram_username',
        'telegram_linked_at',
        'telegram_blocked_at',
        'notif_channel_laporan',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'telegram_chat_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'telegram_linked_at' => 'datetime',
            'telegram_blocked_at' => 'datetime',
            'telegram_chat_id' => 'integer',
        ];
    }

    /**
     * Mengetahui apakah user telah menautkan akun Telegram dan tidak memblokir bot.
     */
    public function hasTelegram(): bool
    {
        return !empty($this->telegram_chat_id) && is_null($this->telegram_blocked_at);
    }

    /**
     * Cek apakah user memiliki role Seller.
     */
    public function isSeller(): bool
    {
        return (int) $this->role_id === Role::SELLER->value;
    }

    /**
     * Cek apakah user memiliki role Admin.
     */
    public function isAdmin(): bool
    {
        return (int) $this->role_id === Role::ADMIN->value;
    }

    public function telegramLinkTokens(): HasMany
    {
        return $this->hasMany(TelegramLinkToken::class, 'user_id');
    }

    public function assignedLaporan(): HasMany
    {
        return $this->hasMany(Laporan::class, 'assigned_to');
    }

    public function laporanPesan(): HasMany
    {
        return $this->hasMany(LaporanPesan::class, 'sender_user_id');
    }

    public function toko(): HasOne
    {
        return $this->hasOne(Toko::class, 'user_id', 'id');
    }

    public function customer(): HasMany
    {
        return $this->hasMany(CustomerModel::class, 'user_id');
    }

    public function order_details(): HasMany
    {
        return $this->hasMany(BeliProdukModel::class, 'user_id');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token, $this->email));
    }
}
