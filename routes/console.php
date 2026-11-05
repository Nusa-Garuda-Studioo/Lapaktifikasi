<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use App\Models\StokAkun;
use App\Models\Pembelian;
use App\Enums\StokStatus;
use App\Enums\PembelianStatus;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

Schedule::call(function () {
    $expiredStok = StokAkun::where('status', StokStatus::RESERVED)
        ->where('reserved_expired_at', '<', now())
        ->get();

    foreach ($expiredStok as $stok) {
        DB::transaction(function () use ($stok) {
            $id_pembelian = $stok->id_pembelian;

            $stok->update([
                'status' => StokStatus::TERSEDIA,
                'reserved_at' => null,
                'reserved_expired_at' => null,
                'id_pembelian' => null,
            ]);

            if ($id_pembelian) {
                $pembelian = Pembelian::where('id_pembelian', $id_pembelian)
                    ->where('status', PembelianStatus::PENDING)
                    ->first();
                if ($pembelian) {
                    Pembelian::$sumberPerubahan = 'scheduler_expired';
                    $pembelian->update(['status' => PembelianStatus::EXPIRED]);
                    Pembelian::$sumberPerubahan = null;
                }
            }
        });
    }
})->everyMinute();

Schedule::command('pakasir:expire-transactions')->everyFiveMinutes();
Schedule::command('tripay:reconcile-transactions')->everyFiveMinutes();
Schedule::command('seller:evaluate-badges')->daily();
Schedule::command('seller:enforce-telegram')->daily();
Schedule::command('laporan:check-sla')->everyFiveMinutes();
