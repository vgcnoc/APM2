<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Jalankan pengecekan isolir setiap jam agar jam isolir yang ditentukan dieksekusi tepat waktu
Schedule::command('billing:suspend-overdue')->hourly();

// Generate invoice otomatis setiap hari pada jam 00:05 (akan dijalankan berdasarkan invoice_issue_date di pengaturan)
Schedule::command('app:generate-invoices')->dailyAt('00:05');

// Pengecekan voucher expired setiap menit
Schedule::command('vouchers:expire')->everyMinute();

// Auto Generate Reward Absen Bulanan
Schedule::command('attendance:generate-rewards')->hourly();
