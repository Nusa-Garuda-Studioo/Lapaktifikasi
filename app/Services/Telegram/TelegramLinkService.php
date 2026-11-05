<?php

namespace App\Services\Telegram;

use App\Models\ProdukModel;
use App\Models\TelegramLinkToken;
use App\Models\Toko;
use App\Models\User;
use App\Services\Telegram\Exceptions\TelegramLinkException;
use Illuminate\Support\Facades\DB;

class TelegramLinkService
{
    /**
     * Buat token penautan akun Telegram untuk user.
     * Token plaintext dikembalikan sekali bersama tautan bot,
     * sedangkan hanya hash sha256 yang disimpan di database.
     *
     * @return array{token: string, url: string, expires_at: \Illuminate\Support\Carbon}
     */
    public function createLinkToken(User $user): array
    {
        // Invalidate token lama milik user
        TelegramLinkToken::where('user_id', $user->id)->delete();

        $plainToken = bin2hex(random_bytes(16));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = now()->addMinutes(15);

        TelegramLinkToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at' => null,
        ]);

        $botUsername = (string) config('services.telegram.bot_username');
        $url = "https://t.me/{$botUsername}?start={$plainToken}";

        return [
            'token' => $plainToken,
            'url' => $url,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Tautkan akun Telegram ke user berdasarkan token.
     *
     * @throws TelegramLinkException
     */
    public function link(string $token, int $chatId, ?string $username = null, string $chatType = 'private'): User
    {
        if ($chatType !== 'private') {
            throw new TelegramLinkException('Penautan akun hanya dapat dilakukan melalui chat pribadi (private chat) dengan bot.');
        }

        $tokenHash = hash('sha256', trim($token));
        $linkToken = TelegramLinkToken::where('token_hash', $tokenHash)->first();

        if (!$linkToken) {
            throw new TelegramLinkException('Token penautan tidak valid. Silakan buat tautan baru melalui website Lapaktifikasi.');
        }

        if ($linkToken->isUsed()) {
            throw new TelegramLinkException('Token penautan ini sudah pernah digunakan. Silakan buat tautan baru melalui website Lapaktifikasi.');
        }

        if ($linkToken->isExpired()) {
            throw new TelegramLinkException('Token penautan sudah kedaluwarsa (masa berlaku 15 menit). Silakan buat tautan baru melalui website Lapaktifikasi.');
        }

        // Cek apakah chatId sudah tertaut ke user lain
        $chatInUse = User::where('telegram_chat_id', $chatId)
            ->where('id', '!=', $linkToken->user_id)
            ->exists();

        if ($chatInUse) {
            throw new TelegramLinkException('Akun Telegram ini sudah tertaut dengan akun Lapaktifikasi yang lain.');
        }

        return DB::transaction(function () use ($linkToken, $chatId, $username) {
            /** @var User $user */
            $user = $linkToken->user()->lockForUpdate()->first();

            if (!$user) {
                throw new TelegramLinkException('Data pengguna terkait token ini tidak ditemukan.');
            }

            // Bersihkan format username jika ada prefix '@'
            $cleanUsername = $username ? ltrim($username, '@') : null;

            $user->update([
                'telegram_chat_id' => $chatId,
                'telegram_username' => $cleanUsername,
                'telegram_linked_at' => now(),
                'telegram_blocked_at' => null,
            ]);

            $linkToken->update([
                'used_at' => now(),
            ]);

            // Jika user adalah Seller, sinkronkan data toko dan aktifkan toko/produk
            if ($user->isSeller()) {
                $toko = Toko::where('user_id', $user->id)->first();
                if ($toko) {
                    $toko->update([
                        'telegram_chat_id' => (string) $chatId,
                        'status' => 'aktif',
                    ]);

                    // Otomatis aktifkan kembali produk seller jika toko/seller sebelumnya dinonaktifkan
                    ProdukModel::where('id_toko', $toko->id_toko)->update([
                        'status' => 'aktif',
                    ]);
                }
            }

            return $user;
        });
    }

    /**
     * Putus penautan akun Telegram dari user.
     */
    public function unlink(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $user->update([
                'telegram_chat_id' => null,
                'telegram_username' => null,
                'telegram_linked_at' => null,
                'telegram_blocked_at' => null,
            ]);

            if ($user->isSeller()) {
                $toko = Toko::where('user_id', $user->id)->first();
                if ($toko) {
                    $toko->update([
                        'telegram_chat_id' => null,
                    ]);
                }
            }

            TelegramLinkToken::where('user_id', $user->id)->delete();

            return true;
        });
    }
}

