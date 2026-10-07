<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NetworkTopologyController;
use App\Http\Controllers\OdcController;
use App\Http\Controllers\OdpController;
use App\Http\Controllers\OltController;
use App\Http\Controllers\OntController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialTransactionController;
use App\Http\Controllers\TicketController;
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

    // Client Area (Reseller)
    Route::middleware(['role:reseller|customer'])->prefix('client-area')->name('client-area.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\ResellerClientController::class, 'dashboard'])->name('dashboard');
        Route::post('/generate-vouchers', [\App\Http\Controllers\ResellerClientController::class, 'generateVouchers'])->name('generate-vouchers');
    });

    // Customer Area (Pelanggan)
    Route::middleware(['role:customer|reseller'])->prefix('my')->name('customer-area.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\CustomerAreaController::class, 'dashboard'])->name('dashboard');
        Route::get('/billing', [\App\Http\Controllers\CustomerAreaController::class, 'billing'])->name('billing');
        Route::get('/tickets', [\App\Http\Controllers\CustomerAreaController::class, 'tickets'])->name('tickets');
    });

    // ── Data Customers ─────────────────────────────────────────
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/booking', [CustomerController::class, 'booking'])->name('booking');
        Route::get('/survey', [CustomerController::class, 'survey'])->name('survey');
        Route::get('/installed', [CustomerController::class, 'installed'])->name('installed');
        Route::get('/activation', [CustomerController::class, 'activation'])->name('activation');
        Route::get('/active', [CustomerController::class, 'active'])->name('active');
        Route::get('/isolir', [CustomerController::class, 'isolir'])->name('isolir');
        Route::get('/online', [CustomerController::class, 'online'])->name('online');
        Route::get('/offline', [CustomerController::class, 'offline'])->name('offline');

        // Assign ONT ke pelanggan
        Route::post('/{customer}/assign-ont', [CustomerController::class, 'assignOnt'])
            ->name('assign-ont');

        // Buat Akun Pelanggan (Login)
        Route::post('/{customer}/create-account', [CustomerController::class, 'createAccount'])
            ->name('create-account');

        // Reset Password Akun Pelanggan
        Route::post('/{customer}/reset-password', [CustomerController::class, 'resetPassword'])
            ->name('reset-password');
            
        // Jadwalkan Pasang
        Route::post('/{customer}/assign-install', [CustomerController::class, 'assignInstall'])
            ->name('assign-install');
            
        // Audit Instalasi
        Route::post('/{customer}/audit', [CustomerController::class, 'audit'])
            ->name('audit');
            
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
            
        // Update ONT via Ajax
        Route::post('/{customer}/update-ont-inline', [CustomerController::class, 'updateOntInline'])
            ->name('update-ont-inline');
            
        // Update Pelanggan POST
        Route::post('/{customer}/update', [CustomerController::class, 'update'])
            ->name('update.post');
            
        // Hapus Pelanggan POST
        Route::post('/{customer}/delete', [CustomerController::class, 'destroy'])
            ->name('destroy.post');
            
        // Hapus Semua Data Booking POST
        Route::post('/bulk-destroy', [CustomerController::class, 'bulkDestroy'])
            ->name('customers.bulk-destroy');
    });
    Route::resource('customers', CustomerController::class);

    // ── Infrastruktur (Network Topology) ───────────────────────
    Route::resource('olts', OltController::class);
    Route::post('olts/{olt}/update', [OltController::class, 'update'])->name('olts.update.post');
    Route::post('olts/{olt}/delete', [OltController::class, 'destroy'])->name('olts.destroy.post');

    // Network Topology
    Route::get('network-topology', [NetworkTopologyController::class, 'index'])->name('network-topology.index');

    Route::resource('odcs', OdcController::class);
    Route::post('odcs/{odc}/update', [OdcController::class, 'update'])->name('odcs.update.post');
    Route::post('odcs/{odc}/delete', [OdcController::class, 'destroy'])->name('odcs.destroy.post');

    Route::resource('odps', OdpController::class);
    Route::post('odps/{odp}/update', [OdpController::class, 'update'])->name('odps.update.post');
    Route::post('odps/{odp}/delete', [OdpController::class, 'destroy'])->name('odps.destroy.post');

    Route::resource('onts', OntController::class);
    Route::post('onts/{ont}/update', [OntController::class, 'update'])->name('onts.update.post');
    Route::post('onts/{ont}/delete', [OntController::class, 'destroy'])->name('onts.destroy.post');
    Route::get('find-onu', [OntController::class, 'findOnu'])->name('find-onu.index');

    Route::resource('materials', MaterialController::class);
    Route::resource('employees', \App\Http\Controllers\EmployeeController::class);
    Route::post('employees/{employee}/update', [\App\Http\Controllers\EmployeeController::class, 'update'])->name('employees.update.post');
    Route::post('employees/{employee}/delete', [\App\Http\Controllers\EmployeeController::class, 'destroy'])->name('employees.destroy.post');
    Route::resource('positions', \App\Http\Controllers\PositionController::class);
    Route::post('positions/{position}/update', [\App\Http\Controllers\PositionController::class, 'update'])->name('positions.update.post');
    Route::post('positions/{position}/delete', [\App\Http\Controllers\PositionController::class, 'destroy'])->name('positions.destroy.post');
    Route::post('materials/{material}/update', [MaterialController::class, 'update'])->name('materials.update.post');
    Route::post('materials/{material}/add-stock', [MaterialController::class, 'addStock'])->name('materials.add-stock');
    Route::post('materials/{material}/delete', [MaterialController::class, 'destroy'])->name('materials.destroy.post');

    // FTTH Mapping
    Route::get('ftth', [\App\Http\Controllers\FtthController::class, 'index'])->name('ftth.index');
    Route::get('ftth/{design}', [\App\Http\Controllers\FtthController::class, 'show'])->name('ftth.show');
    Route::get('ftth/api/map-data', [\App\Http\Controllers\FtthController::class, 'mapData'])->name('ftth.map-data');
    Route::post('ftth', [\App\Http\Controllers\FtthController::class, 'store'])->name('ftth.store');
    Route::post('ftth/{design}/update', [\App\Http\Controllers\FtthController::class, 'update'])->name('ftth.update.post');
    Route::post('ftth/{design}/review', [\App\Http\Controllers\FtthController::class, 'submitReview'])->name('ftth.submit-review');
    Route::get('ftth/{design}/export', [\App\Http\Controllers\FtthController::class, 'exportPdf'])->name('ftth.export');
    Route::post('ftth/{design}/delete', [\App\Http\Controllers\FtthController::class, 'destroy'])->name('ftth.destroy.post');
    Route::post('ftth/cable-routes', [\App\Http\Controllers\FtthController::class, 'storeCableRoute'])->name('ftth.cable-routes.store');
    Route::post('ftth/devices', [\App\Http\Controllers\FtthController::class, 'storeDevice'])->name('ftth.devices.store');
    Route::post('ftth/devices/{device}/position', [\App\Http\Controllers\FtthController::class, 'updateDevicePosition'])->name('ftth.devices.position');

    Route::post('material-transactions/bulk-destroy', [MaterialTransactionController::class, 'bulkDestroy'])->name('material-transactions.bulk-destroy');
    Route::resource('material-transactions', MaterialTransactionController::class)->except(['edit', 'update', 'destroy']);
    Route::post('material-transactions/{item}/register-ont', [MaterialTransactionController::class, 'registerOnt'])->name('material-transactions.register-ont');
    Route::post('material-transactions/{item}/reset-ont', [MaterialTransactionController::class, 'resetOnt'])->name('material-transactions.reset-ont');
    Route::post('material-transactions/{material_transaction}/delete', [MaterialTransactionController::class, 'destroy'])->name('material-transactions.destroy');

    // ── Data Jaringan (Dashboard Topology) ─────────────────────
    Route::get('/network-data', [\App\Http\Controllers\NetworkDataController::class, 'index'])->name('network-data.index');

    // ── Vouchers & Profil Voucher ─────────────────────────────
    Route::get('/vouchers/profiles', [\App\Http\Controllers\VoucherProfileController::class, 'index'])->name('vouchers.profiles.index');
    Route::post('/vouchers/profiles', [\App\Http\Controllers\VoucherProfileController::class, 'store'])->name('vouchers.profiles.store');
    Route::post('/vouchers/profiles/{voucherProfile}/update', [\App\Http\Controllers\VoucherProfileController::class, 'update'])->name('vouchers.profiles.update.post');
    Route::post('/vouchers/profiles/{voucherProfile}/delete', [\App\Http\Controllers\VoucherProfileController::class, 'destroy'])->name('vouchers.profiles.destroy.post');

    Route::get('/vouchers', [\App\Http\Controllers\VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/online', [\App\Http\Controllers\VoucherController::class, 'online'])->name('vouchers.online');
    Route::get('/vouchers/offline', [\App\Http\Controllers\VoucherController::class, 'offline'])->name('vouchers.offline');
    Route::get('/vouchers/expired', [\App\Http\Controllers\VoucherController::class, 'expired'])->name('vouchers.expired');
    Route::get('/vouchers/print', [\App\Http\Controllers\VoucherController::class, 'print'])->name('vouchers.print');
    Route::post('/vouchers', [\App\Http\Controllers\VoucherController::class, 'store'])->name('vouchers.store');
    Route::post('/vouchers/{voucher}/toggle-status', [\App\Http\Controllers\VoucherController::class, 'toggleStatus'])->name('vouchers.toggle-status');
    Route::post('/vouchers/bulk-destroy', [\App\Http\Controllers\VoucherController::class, 'bulkDestroy'])->name('vouchers.bulk-destroy');
    Route::post('/vouchers/{voucher}/delete', [\App\Http\Controllers\VoucherController::class, 'destroy'])->name('vouchers.destroy.post');

    // ── Reseller Voucher ───────────────────────────────────────
    Route::resource('resellers', \App\Http\Controllers\ResellerController::class)->except(['create', 'show', 'edit']);
    Route::post('/resellers/{reseller}/update', [\App\Http\Controllers\ResellerController::class, 'update'])->name('resellers.update.post');
    Route::post('/resellers/{reseller}/delete', [\App\Http\Controllers\ResellerController::class, 'destroy'])->name('resellers.destroy.post');
    Route::post('/resellers/{reseller}/create-account', [\App\Http\Controllers\ResellerController::class, 'createAccount'])->name('resellers.create-account');
    Route::post('/resellers/{reseller}/reset-password', [\App\Http\Controllers\ResellerController::class, 'resetPassword'])->name('resellers.reset-password');
    
    // ── Reseller Requests (Admin) ───────────────────────────────────────
    Route::get('/reseller-requests', [\App\Http\Controllers\ResellerBalanceRequestController::class, 'index'])->name('reseller-requests.index');
    Route::post('/reseller-requests', [\App\Http\Controllers\ResellerBalanceRequestController::class, 'store'])->name('reseller-requests.store');
    Route::post('/reseller-requests/{balanceRequest}/approve', [\App\Http\Controllers\ResellerBalanceRequestController::class, 'approve'])->name('reseller-requests.approve');
    Route::post('/reseller-requests/{balanceRequest}/reject', [\App\Http\Controllers\ResellerBalanceRequestController::class, 'reject'])->name('reseller-requests.reject');

    // ── Reseller Billing (Penagihan & Pelunasan) ────────────────────────
    Route::get('/reseller-billing', [\App\Http\Controllers\ResellerBillingController::class, 'index'])->name('reseller-billing.index');
    Route::post('/reseller-billing/{invoice}/collect', [\App\Http\Controllers\ResellerBillingController::class, 'collect'])->name('reseller-billing.collect');
    
    Route::get('/reseller-settlements', [\App\Http\Controllers\ResellerBillingController::class, 'settlements'])->name('reseller-settlements.index');
    Route::post('/reseller-settlements/{payment}/approve', [\App\Http\Controllers\ResellerBillingController::class, 'approve'])->name('reseller-settlements.approve');

    Route::get('/reseller-reports', [\App\Http\Controllers\ResellerReportController::class, 'index'])->name('reseller-reports.index');
    Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.destroy');


    // ── Pengguna & Hak Akses ───────────────────────────────────
    Route::resource('users', \App\Http\Controllers\UserController::class)->except(['create', 'show', 'edit'])->middleware('role:admin');
    Route::post('users/{user}/update', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update.post')->middleware('role:admin');
    Route::post('users/{user}/delete', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy.post');

    // ── Billing & Keuangan ─────────────────────────────────────
    Route::post('invoices/{invoice}/pay', [\App\Http\Controllers\InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::post('invoices/{invoice}/rollback', [\App\Http\Controllers\InvoiceController::class, 'rollback'])->name('invoices.rollback');
    Route::post('invoices/{invoice}/promise', [\App\Http\Controllers\InvoiceController::class, 'promise'])->name('invoices.promise');
    Route::get('invoices/{invoice}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    
    Route::get('tax-reports/print', [\App\Http\Controllers\TaxReportController::class, 'print'])->name('tax.reports.print');
    Route::get('tax-reports', [\App\Http\Controllers\TaxReportController::class, 'index'])->name('tax.reports.index');
    // Route::resource('payments', PaymentController::class);

    // ── Customer Billing (Penagihan Lapangan) ─────────────────────
    Route::get('/customer-billing', [\App\Http\Controllers\CustomerBillingController::class, 'index'])->name('customer-billing.index');
    Route::post('/customer-billing/{invoice}/collect', [\App\Http\Controllers\CustomerBillingController::class, 'collect'])->name('customer-billing.collect');
    
    Route::get('/customer-settlements', [\App\Http\Controllers\CustomerBillingController::class, 'settlements'])->name('customer-settlements.index');
    Route::post('/customer-settlements/{payment}/approve', [\App\Http\Controllers\CustomerBillingController::class, 'approve'])->name('customer-settlements.approve');

    // ── Laporan Keuangan Global & Pelanggan ────────────────────────────────
    Route::get('/financial-reports', [\App\Http\Controllers\FinancialReportController::class, 'index'])->name('financial-reports.index');
    Route::get('/customer-reports', [\App\Http\Controllers\CustomerReportController::class, 'index'])->name('customer-reports.index');

    // ── Ticketing & Gangguan ───────────────────────────────────
    Route::resource('tickets', TicketController::class);
    // Route::resource('schedules', TechnicianScheduleController::class);

    // ── Penggajian & Insentif (Payroll) ────────────────────────
    Route::get('/payroll', [\App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll/settings', [\App\Http\Controllers\PayrollController::class, 'updateSettings'])->name('payroll.settings.update');
    Route::post('/payroll/{user}/incentives', [\App\Http\Controllers\PayrollController::class, 'storeIncentive'])->name('payroll.incentive.store');
    Route::delete('/payroll/incentives/{incentive}', [\App\Http\Controllers\PayrollController::class, 'destroyIncentive'])->name('payroll.incentive.destroy');
    Route::post('/payroll/{user}/deductions', [\App\Http\Controllers\PayrollController::class, 'storeDeduction'])->name('payroll.deduction.store');
    Route::delete('/payroll/deductions/{deduction}', [\App\Http\Controllers\PayrollController::class, 'destroyDeduction'])->name('payroll.deduction.destroy');
    Route::post('/payroll/{user}/disburse', [\App\Http\Controllers\PayrollController::class, 'disburse'])->name('payroll.disburse');

    // Insentif & Potongan List
    Route::get('/insentif-potongan', [\App\Http\Controllers\IncentiveDeductionController::class, 'index'])->name('insentif-potongan.index');
    Route::delete('/insentif-potongan/destroy', [\App\Http\Controllers\IncentiveDeductionController::class, 'destroyRecord'])->name('insentif-potongan.destroy');
    
    // Master Insentif & Potongan
    Route::resource('settings/payroll-categories', \App\Http\Controllers\PayrollCategoryController::class);
    Route::get('api/payroll-categories/active', [\App\Http\Controllers\PayrollCategoryController::class, 'getActiveCategories'])->name('payroll-categories.active');

    // ── Master Data & Pengaturan ───────────────────────────────
    Route::resource('settings/areas', \App\Http\Controllers\AreaController::class);
    Route::post('settings/areas/{area}/update', [\App\Http\Controllers\AreaController::class, 'update'])->name('areas.update.post');
    Route::post('settings/areas/{area}/delete', [\App\Http\Controllers\AreaController::class, 'destroy'])->name('areas.destroy.post');
    
    Route::get('/settings/branding', [\App\Http\Controllers\SettingController::class, 'branding'])->name('settings.branding');
    Route::post('/settings/branding', [\App\Http\Controllers\SettingController::class, 'updateBranding'])->name('settings.branding.update');
    
    Route::get('/settings/billing', [\App\Http\Controllers\SettingController::class, 'billing'])->name('settings.billing');
    Route::post('/settings/billing', [\App\Http\Controllers\SettingController::class, 'updateBilling'])->name('settings.billing.update');

    Route::get('/settings/api', function () {
        return inertia('Settings/Api');
    })->name('settings.api');

    Route::get('/settings/resellers', [\App\Http\Controllers\ResellerSettingController::class, 'index'])->name('settings.resellers.index');
    Route::post('/settings/resellers/{user}/update', [\App\Http\Controllers\ResellerSettingController::class, 'update'])->name('settings.resellers.update.post');

    Route::resource('settings/roles', \App\Http\Controllers\RoleController::class)->except(['create', 'show', 'edit']);
    Route::post('settings/roles/{role}/update', [\App\Http\Controllers\RoleController::class, 'update'])->name('roles.update.post');
    Route::post('settings/roles/{role}/delete', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy.post');
    Route::post('/settings/api/test', [\App\Http\Controllers\SettingController::class, 'apiTest'])->name('settings.api.test');
    Route::post('/settings/api/sync', [\App\Http\Controllers\SettingController::class, 'apiSync'])->name('settings.api.sync');

    Route::post('/settings/api/token', function (Illuminate\Http\Request $request) {
        $user = $request->user();
        $user->tokens()->delete(); // Hapus token lama
        $token = $user->createToken('Integrasi-app-LK')->plainTextToken;
        return response()->json(['token' => $token]);
    })->name('settings.api.token');
    Route::resource('internet-packages', \App\Http\Controllers\InternetPackageController::class)->except(['create', 'show', 'edit']);
    Route::post('internet-packages/{internet_package}/update', [\App\Http\Controllers\InternetPackageController::class, 'update'])->name('internet-packages.update.post');
    Route::post('internet-packages/{internet_package}/delete', [\App\Http\Controllers\InternetPackageController::class, 'destroy'])->name('internet-packages.destroy.post');

    // ── Data Router ────────────────────────────────────────────
    Route::resource('routers', \App\Http\Controllers\RouterController::class)->except(['create', 'show', 'edit']);
    Route::post('routers/{router}/update', [\App\Http\Controllers\RouterController::class, 'update'])->name('routers.update.post');
    Route::post('routers/{router}/delete', [\App\Http\Controllers\RouterController::class, 'destroy'])->name('routers.destroy.post');
    Route::get('routers/{router}/ping', [\App\Http\Controllers\RouterController::class, 'ping'])->name('routers.ping');

    // ── RADIUS Billing System ──────────────────────────────────
    Route::prefix('radius')->name('radius.')->group(function () {
        Route::resource('nas', \App\Http\Controllers\Radius\NasController::class)->except(['create', 'show', 'edit']);
        Route::post('nas/{nas}/update', [\App\Http\Controllers\Radius\NasController::class, 'update'])->name('nas.update.post');
        Route::post('nas/{nas}/delete', [\App\Http\Controllers\Radius\NasController::class, 'destroy'])->name('nas.destroy.post');
        Route::post('nas/{nas}/regenerate', [\App\Http\Controllers\Radius\NasController::class, 'regenerate'])->name('nas.regenerate');
        Route::get('nas/{nas}/ping', [\App\Http\Controllers\Radius\NasController::class, 'ping'])->name('nas.ping');

        Route::get('online-users', [\App\Http\Controllers\Radius\OnlineUserController::class, 'index'])->name('online-users.index');
        Route::post('online-users/disconnect', [\App\Http\Controllers\Radius\OnlineUserController::class, 'disconnect'])->name('online-users.disconnect');

        Route::get('auth-logs', [\App\Http\Controllers\Radius\AuthLogController::class, 'index'])->name('auth-logs.index');
    });
});
