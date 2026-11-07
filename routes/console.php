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

// Batch auto-release expired reservations every minute with composite index support
Schedule::command('app:release-expired-reservations')->everyMinute()->withoutOverlapping();

Schedule::command('pakasir:expire-transactions')->everyFiveMinutes();
Schedule::command('tripay:reconcile-transactions')->everyFiveMinutes();
Schedule::command('seller:evaluate-badges')->daily();
Schedule::command('seller:enforce-telegram')->daily();
Schedule::command('laporan:check-sla')->everyFiveMinutes();
