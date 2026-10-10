<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup harian pukul 02:00.
Schedule::command('kasirku:backup')->dailyAt('02:00')->withoutOverlapping();

// Periksa peringatan operasional setiap 30 menit: stok menipis,
// shift yang menggantung, utang pelanggan & supplier yang lewat tempo.
Schedule::command('kasirku:alerts')->everyThirtyMinutes()->withoutOverlapping();