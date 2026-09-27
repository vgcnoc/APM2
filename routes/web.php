<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OdcController;
use App\Http\Controllers\OdpController;
use App\Http\Controllers\OltController;
use App\Http\Controllers\OntController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialTransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - ISP Management System
|--------------------------------------------------------------------------
*/

// ── Auth Routes (dari Laravel Breeze) ──────────────────────────
require __DIR__ . '/auth.php';

// ── Protected Routes ───────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

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
            
        // Jadwalkan Pasang
        Route::post('/{customer}/assign-install', [CustomerController::class, 'assignInstall'])
            ->name('assign-install');
            
        // Aktivasi Pelanggan
        Route::post('/{customer}/activate', [CustomerController::class, 'activate'])
            ->name('activate');
            
        // Jadwalkan Survey
        Route::post('/{customer}/assign-survey', [CustomerController::class, 'assignSurvey'])
            ->name('assign-survey');
            
        // Minta Jadwal Survey
        Route::post('/{customer}/request-survey', [CustomerController::class, 'requestSurvey'])
            ->name('request-survey');
            
        // Simpan Hasil Survey
        Route::post('/{customer}/store-survey', [CustomerController::class, 'storeSurvey'])
            ->name('store-survey');
            
        // Pindah ke tahap Instalasi
        Route::post('/{customer}/mark-installing', [CustomerController::class, 'markInstalling'])
            ->name('mark-installing');
    });
    Route::resource('customers', CustomerController::class);

    // ── Infrastruktur (Network Topology) ───────────────────────
    Route::resource('olts', OltController::class);
    Route::resource('odcs', OdcController::class);
    Route::resource('odps', OdpController::class);
    Route::resource('onts', OntController::class);
    Route::resource('materials', MaterialController::class);
    Route::resource('material-transactions', MaterialTransactionController::class)->except(['edit', 'update', 'destroy']);
    Route::post('material-transactions/{item}/register-ont', [MaterialTransactionController::class, 'registerOnt'])->name('material-transactions.register-ont');
    Route::post('material-transactions/{material_transaction}/delete', [MaterialTransactionController::class, 'destroy'])->name('material-transactions.destroy');

    // ── Billing & Keuangan ─────────────────────────────────────
    // Route::resource('invoices', InvoiceController::class);
    // Route::resource('payments', PaymentController::class);

    // ── Ticketing & Gangguan ───────────────────────────────────
    // Route::resource('tickets', TicketController::class);
    // Route::resource('schedules', TechnicianScheduleController::class);

    // ── Master Data & Pengaturan ───────────────────────────────
    Route::resource('settings/areas', \App\Http\Controllers\AreaController::class);
    
    Route::get('/settings/api', function () {
        return inertia('Settings/Api');
    })->name('settings.api');

    Route::post('/settings/api/token', function (Illuminate\Http\Request $request) {
        $user = $request->user();
        $user->tokens()->delete(); // Hapus token lama
        $token = $user->createToken('Integrasi-app-LK')->plainTextToken;
        return response()->json(['token' => $token]);
    })->name('settings.api.token');
    // Route::resource('users', UserController::class)->middleware('role:admin');
    // Route::resource('packages', InternetPackageController::class);
});
