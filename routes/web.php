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

        // Reschedule Survey
        Route::post('/{customer}/reschedule-survey', [CustomerController::class, 'rescheduleSurvey'])
            ->name('reschedule-survey');
            
        // Minta Jadwal Survey
        Route::post('/{customer}/request-survey', [CustomerController::class, 'requestSurvey'])
            ->name('request-survey');
            
        // Simpan Hasil Survey
        Route::post('/{customer}/store-survey', [CustomerController::class, 'storeSurvey'])
            ->name('store-survey');
            
        // Pindah ke tahap Instalasi
        Route::post('/{customer}/mark-installing', [CustomerController::class, 'markInstalling'])
            ->name('mark-installing');
            
        // Update Pelanggan POST
        Route::post('/{customer}/update', [CustomerController::class, 'update'])
            ->name('update.post');
            
        // Hapus Pelanggan POST
        Route::post('/{customer}/delete', [CustomerController::class, 'destroy'])
            ->name('destroy.post');
    });
    Route::resource('customers', CustomerController::class);

    // ── Infrastruktur (Network Topology) ───────────────────────
    Route::resource('olts', OltController::class);
    Route::post('olts/{olt}/update', [OltController::class, 'update'])->name('olts.update.post');
    Route::post('olts/{olt}/delete', [OltController::class, 'destroy'])->name('olts.destroy.post');

    Route::resource('odcs', OdcController::class);
    Route::post('odcs/{odc}/update', [OdcController::class, 'update'])->name('odcs.update.post');
    Route::post('odcs/{odc}/delete', [OdcController::class, 'destroy'])->name('odcs.destroy.post');

    Route::resource('odps', OdpController::class);
    Route::post('odps/{odp}/update', [OdpController::class, 'update'])->name('odps.update.post');
    Route::post('odps/{odp}/delete', [OdpController::class, 'destroy'])->name('odps.destroy.post');

    Route::resource('onts', OntController::class);
    Route::post('onts/{ont}/update', [OntController::class, 'update'])->name('onts.update.post');
    Route::post('onts/{ont}/delete', [OntController::class, 'destroy'])->name('onts.destroy.post');

    Route::resource('materials', MaterialController::class);
    Route::post('materials/{material}/update', [MaterialController::class, 'update'])->name('materials.update.post');
    Route::post('materials/{material}/add-stock', [MaterialController::class, 'addStock'])->name('materials.add-stock');
    Route::post('materials/{material}/delete', [MaterialController::class, 'destroy'])->name('materials.destroy.post');

    Route::resource('material-transactions', MaterialTransactionController::class)->except(['edit', 'update', 'destroy']);
    Route::post('material-transactions/{item}/register-ont', [MaterialTransactionController::class, 'registerOnt'])->name('material-transactions.register-ont');
    Route::post('material-transactions/{item}/reset-ont', [MaterialTransactionController::class, 'resetOnt'])->name('material-transactions.reset-ont');
    Route::post('material-transactions/{material_transaction}/delete', [MaterialTransactionController::class, 'destroy'])->name('material-transactions.destroy');

    // ── Pengguna & Hak Akses ───────────────────────────────────
    Route::resource('users', \App\Http\Controllers\UserController::class)->except(['create', 'show', 'edit']);
    Route::post('users/{user}/update', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update.post');
    Route::post('users/{user}/delete', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy.post');

    // ── Billing & Keuangan ─────────────────────────────────────
    // Route::resource('invoices', InvoiceController::class);
    // Route::resource('payments', PaymentController::class);

    // ── Ticketing & Gangguan ───────────────────────────────────
    // Route::resource('tickets', TicketController::class);
    // Route::resource('schedules', TechnicianScheduleController::class);

    // ── Master Data & Pengaturan ───────────────────────────────
    Route::resource('settings/areas', \App\Http\Controllers\AreaController::class);
    Route::post('settings/areas/{area}/update', [\App\Http\Controllers\AreaController::class, 'update'])->name('areas.update.post');
    Route::post('settings/areas/{area}/delete', [\App\Http\Controllers\AreaController::class, 'destroy'])->name('areas.destroy.post');
    
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
