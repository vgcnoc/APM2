<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Issue invoices before checking same-day midnight isolation deadlines.
$invoiceSchedule = Schedule::command('app:generate-invoices')->dailyAt('00:00')
    ->timezone(config('billing.timezone', 'Asia/Jakarta'))->withoutOverlapping(60);
$isolationSchedule = Schedule::command('billing:suspend-overdue')->everyMinute()->withoutOverlapping(60);
if ($billingUser = config('billing.scheduler_user')) {
    $invoiceSchedule->user($billingUser);
    $isolationSchedule->user($billingUser);
}

// Pengecekan voucher expired setiap menit
Schedule::command('vouchers:expire')->everyMinute();

// Auto Generate Reward Absen Bulanan
Schedule::command('attendance:generate-rewards')->hourly();

// Auto Evaluasi Presensi Cerdas & Rekap Awal s/d Akhir Bulan
Schedule::command('attendance:auto-evaluate')->dailyAt('23:30')->timezone('Asia/Jakarta');
