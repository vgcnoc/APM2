<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('geo:clean-addresses', function () {
    $res = \App\Services\GeoLocationMasterService::cleanDatabaseAddresses();
    $this->info("Database addresses cleaned successfully. Total scanned: {$res['total_scanned']}, updated customers: {$res['updated_customers']}.");
})->purpose('Clean and standardize customer addresses and area coverage');

$billingTimezone = config('billing.timezone', 'Asia/Jakarta');

// Issue invoices at 00:00 WIB and re-check every 15 minutes so missed midnight windows or same-day setting changes self-heal automatically.
$invoiceSchedule = Schedule::command('app:generate-invoices')
    ->everyFifteenMinutes()
    ->timezone($billingTimezone)
    ->withoutOverlapping(60);
$isolationSchedule = Schedule::command('billing:suspend-overdue')
    ->everyMinute()
    ->withoutOverlapping(60);

// Only switch user via sudo when the scheduler runs as root (UID 0).
// When cron already runs inside www-data's crontab, ->user('www-data') wraps the command in `sudo -u www-data`, which fails without sudo privileges.
$billingUser = config('billing.scheduler_user');
$isRunningAsRoot = function_exists('posix_geteuid') && posix_geteuid() === 0;
if ($billingUser && $isRunningAsRoot) {
    $currentProcessUser = function_exists('posix_getpwuid')
        ? (posix_getpwuid(posix_geteuid())['name'] ?? null)
        : null;
    if ($currentProcessUser !== $billingUser) {
        $invoiceSchedule->user($billingUser);
        $isolationSchedule->user($billingUser);
    }
}

// Pengecekan voucher expired setiap menit
Schedule::command('vouchers:expire')->everyMinute();

// Auto Generate Reward Absen Bulanan
Schedule::command('attendance:generate-rewards')->hourly();

// Auto Evaluasi Presensi Cerdas & Rekap Awal s/d Akhir Bulan + Auto Sinkronisasi ke Payroll
Schedule::command('attendance:auto-evaluate --sync-payroll')->dailyAt('23:30')->timezone('Asia/Jakarta');
