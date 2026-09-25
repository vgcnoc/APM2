<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OdcController;
use App\Http\Controllers\OdpController;
use App\Http\Controllers\OltController;
use App\Http\Controllers\OntController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - ISP Management System
|--------------------------------------------------------------------------
*/

// ── Auth Routes (dari Laravel Breeze) ──────────────────────────
require __DIR__ . '/auth.php';

// ── Protected Routes ───────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Data Customers ─────────────────────────────────────────
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/booking', [CustomerController::class, 'booking'])->name('booking');
        Route::get('/survey', [CustomerController::class, 'survey'])->name('survey');
        Route::get('/installed', [CustomerController::class, 'installed'])->name('installed');

        // Assign ONT ke pelanggan
        Route::post('/{customer}/assign-ont', [CustomerController::class, 'assignOnt'])
            ->name('assign-ont');
    });
    Route::resource('customers', CustomerController::class);

    // ── Infrastruktur (Network Topology) ───────────────────────
    Route::resource('olts', OltController::class);
    Route::resource('odcs', OdcController::class);
    Route::resource('odps', OdpController::class);
    Route::resource('onts', OntController::class);

    // ── Billing & Keuangan ─────────────────────────────────────
    // Route::resource('invoices', InvoiceController::class);
    // Route::resource('payments', PaymentController::class);

    // ── Ticketing & Gangguan ───────────────────────────────────
    // Route::resource('tickets', TicketController::class);
    // Route::resource('schedules', TechnicianScheduleController::class);

    // ── Master Data & Pengaturan ───────────────────────────────
    // Route::resource('users', UserController::class)->middleware('role:admin');
    // Route::resource('packages', InternetPackageController::class);
});
