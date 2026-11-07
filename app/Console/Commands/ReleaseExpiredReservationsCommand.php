<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Pembelian;
use App\Models\StokAkun;
use App\Enums\PembelianStatus;
use App\Enums\StokStatus;

class ReleaseExpiredReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:release-expired-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batch release expired stock reservations and expire overdue pending purchases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $releasedPurchasesCount = 0;
        $releasedStockCount = 0;

        // 1. Ambil transaksi pembelian PENDING yang reserved_until sudah kedaluwarsa
        $expiredPurchases = Pembelian::where('status', PembelianStatus::PENDING)
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<', $now)
            ->with('stokAkun')
            ->get();

        foreach ($expiredPurchases as $pembelian) {
            DB::transaction(function () use ($pembelian, &$releasedPurchasesCount, &$releasedStockCount) {
                // Rilis stok akun yang di-reserve
                if ($pembelian->id_stok) {
                    $stok = StokAkun::find($pembelian->id_stok);
                    if ($stok && $stok->status === StokStatus::RESERVED) {
                        $stok->update([
                            'status' => StokStatus::TERSEDIA,
                            'reserved_at' => null,
                            'reserved_expired_at' => null,
                            'id_pembelian' => null,
                        ]);
                        $releasedStockCount++;
                    }
                }

                Pembelian::$sumberPerubahan = 'scheduler_batch_release';
                $pembelian->update(['status' => PembelianStatus::EXPIRED]);
                Pembelian::$sumberPerubahan = null;

                $releasedPurchasesCount++;
            });
        }

        // 2. Bersihkan orphan reserved stock (stok yang berstatus RESERVED dan reserved_expired_at lewat)
        $orphanUpdated = StokAkun::where('status', StokStatus::RESERVED)
            ->whereNotNull('reserved_expired_at')
            ->where('reserved_expired_at', '<', $now)
            ->update([
                'status' => StokStatus::TERSEDIA,
                'reserved_at' => null,
                'reserved_expired_at' => null,
                'id_pembelian' => null,
            ]);

        $totalReleasedStock = $releasedStockCount + $orphanUpdated;

        if ($releasedPurchasesCount > 0 || $totalReleasedStock > 0) {
            $msg = "Auto-released: {$releasedPurchasesCount} expired purchases, {$totalReleasedStock} stock accounts restored to TERSEDIA.";
            $this->info($msg);
            Log::info("ReleaseExpiredReservationsCommand: {$msg}");
        } else {
            $this->line('No expired reservations found.');
        }

        return Command::SUCCESS;
    }
}
