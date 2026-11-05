<?php

namespace App\Console\Commands;

use App\Models\ProdukModel;
use App\Models\SettingWebsite;
use App\Models\Toko;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SellerEnforceTelegramCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seller:enforce-telegram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menonaktifkan toko dan produk seller yang melewati batas masa tenggang tanpa menautkan Telegram.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa masa tenggang penautan Telegram bagi seller...');

        $now = now();
        $defaultGraceDays = SettingWebsite::getCached()?->telegram_seller_grace_days ?? 14;

        // Ambil semua toko dengan relasi user
        $tokos = Toko::with('user')->get();
        $deactivatedCount = 0;

        foreach ($tokos as $toko) {
            $user = $toko->user;

            // Jika user tidak ditemukan, lewati
            if (!$user) {
                continue;
            }

            // Jika user sudah menautkan Telegram dan tidak memblokir bot, seller aman
            if ($user->hasTelegram()) {
                continue;
            }

            // Jika batas masa tenggang belum tercatat, inisialisasi dari tanggal dibuatnya toko/user
            if (empty($toko->telegram_grace_due_at)) {
                $baseDate = $toko->created_at ?: now();
                $toko->telegram_grace_due_at = (clone $baseDate)->addDays($defaultGraceDays);
                $toko->save();
            }

            // Cek apakah sudah melewati batas masa tenggang
            if ($toko->telegram_grace_due_at->isPast()) {
                $affectedProducts = 0;

                // Nonaktifkan toko jika masih aktif
                if ($toko->status === 'aktif') {
                    $toko->update(['status' => 'nonaktif']);
                }

                // Nonaktifkan semua produk aktif milik toko ini
                $affectedProducts = ProdukModel::where('id_toko', $toko->id_toko)
                    ->where('status', 'aktif')
                    ->update(['status' => 'nonaktif']);

                $deactivatedCount++;
                $reason = $user->telegram_blocked_at ? 'bot telegram diblokir' : 'belum menautkan telegram';

                $this->warn("Toko #{$toko->id_toko} ('{$toko->nama_toko}') dinonaktifkan ({$reason}). {$affectedProducts} produk dinonaktifkan.");
                Log::warning("seller:enforce-telegram: Toko #{$toko->id_toko} dinonaktifkan ({$reason}). {$affectedProducts} produk dinonaktifkan.");
            }
        }

        $this->info("Pemeriksaan selesai. Total {$deactivatedCount} toko dinonaktifkan karena melewati batas masa tenggang.");

        return Command::SUCCESS;
    }
}

