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
use App\Http\Controllers\MaterialReturnController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - ISP Management System
|--------------------------------------------------------------------------
*/

// ── Public Routes (Tanpa Login) ─────────────────────────────────
Route::get('/public/check-bill', function (\Illuminate\Http\Request $request) {
    $q = trim($request->input('q', ''));
    if (strlen($q) < 3) {
        return response()->json(['success' => false, 'message' => 'Masukkan minimal 3 karakter ID Pelanggan atau No WhatsApp.']);
    }
    $customer = \App\Models\Customer::with(['package', 'areaModel'])
        ->where(function ($sub) use ($q) {
            $sub->where('customer_code', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%");
        })
        ->first();

    if (!$customer) {
        return response()->json(['success' => false, 'message' => 'Data pelanggan tidak ditemukan. Pastikan ID Pelanggan atau No. WhatsApp sudah sesuai.']);
    }

    $unpaid = \App\Models\Invoice::with('payments')
        ->where('customer_id', $customer->id)
        ->where('is_reseller_balance', false)
        ->whereIn('status', ['unpaid', 'partial', 'overdue'])
        ->latest('due_date')
        ->get()
        ->map(function ($inv) {
            $verifiedPaid = $inv->payments->where('status', 'verified')->sum('amount');
            return [
                'invoice_number' => $inv->invoice_number,
                'period_label' => $inv->period_label,
                'due_date' => $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->translatedFormat('d M Y') : '-',
                'amount' => (float) $inv->amount,
                'remaining' => max(0, (float) ($inv->amount - $verifiedPaid)),
                'status' => $inv->status,
            ];
        })
        ->filter(fn($i) => $i['remaining'] > 0)
        ->values();

    return response()->json([
        'success' => true,
        'customer' => [
            'name' => $customer->name,
            'customer_code' => $customer->customer_code,
            'package_name' => $customer->package?->name ?? 'Internet Rumah',
            'package_price' => (float) ($customer->package?->price ?? 0),
            'area' => $customer->areaModel?->name ?? $customer->area ?? '-',
            'status' => $customer->status,
        ],
        'invoices' => $unpaid,
        'total_unpaid' => (float) $unpaid->sum('remaining'),
    ]);
})->name('public.check-bill');

// ── Auth Routes (dari Laravel Breeze) ──────────────────────────
require __DIR__ . '/auth.php';

// ── Protected Routes ───────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::post('/profile/toggle-duty', [\App\Http\Controllers\UserController::class, 'toggleDuty'])->name('profile.toggle-duty');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Client Area (Reseller)
    Route::middleware(['role:reseller|customer'])->prefix('client-area')->name('client-area.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\ResellerClientController::class, 'dashboard'])->name('dashboard');
        Route::get('/billing', [\App\Http\Controllers\ResellerClientController::class, 'billing'])->name('billing');
        Route::get('/biller', [\App\Http\Controllers\ResellerClientController::class, 'biller'])->name('biller');
        Route::get('/biller/search-customer', [\App\Http\Controllers\ResellerClientController::class, 'searchCustomer'])->name('biller.search-customer');
        Route::post('/biller/pay/{invoice}', [\App\Http\Controllers\ResellerClientController::class, 'payCustomerInvoice'])->name('biller.pay');
        Route::post('/generate-vouchers', [\App\Http\Controllers\ResellerClientController::class, 'generateVouchers'])->name('generate-vouchers');
        Route::post('/top-up-balance', [\App\Http\Controllers\PaymentGatewayController::class, 'topUpReseller'])->name('top-up-balance');
    });

    // Customer Area (Pelanggan)
    Route::middleware(['role:customer|reseller'])->prefix('my')->name('customer-area.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\CustomerAreaController::class, 'dashboard'])->name('dashboard');
        Route::get('/billing', [\App\Http\Controllers\CustomerAreaController::class, 'billing'])->name('billing');
        Route::get('/tickets', [\App\Http\Controllers\CustomerAreaController::class, 'tickets'])->name('tickets');
    });

    // Payment Gateway (Pelanggan & Reseller)
    Route::prefix('payment-gateway')->name('payment-gateway.')->group(function () {
        Route::post('/checkout', [\App\Http\Controllers\PaymentGatewayController::class, 'checkout'])->name('checkout');
        Route::get('/pay/{invoice}', [\App\Http\Controllers\PaymentGatewayController::class, 'pay'])->name('pay');
        Route::post('/simulate/{invoice}', [\App\Http\Controllers\PaymentGatewayController::class, 'simulateSuccess'])->name('simulate');
        Route::get('/status/{invoice}', [\App\Http\Controllers\PaymentGatewayController::class, 'pay'])->name('status');
    });

    // ── Data Customers ─────────────────────────────────────────
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/booking', [CustomerController::class, 'booking'])->name('booking');
        Route::get('/survey', [CustomerController::class, 'survey'])->name('survey');
        Route::get('/installed', [CustomerController::class, 'installed'])->name('installed');
        Route::get('/activation', [CustomerController::class, 'activation'])->name('activation');
        Route::get('/active', [CustomerController::class, 'active'])->name('active');
        Route::get('/gratis', [CustomerController::class, 'gratis'])->name('gratis');
        Route::post('/{customer}/toggle-service-status', [CustomerController::class, 'toggleServiceStatus'])->name('toggle-service-status');
        Route::get('/isolir', [CustomerController::class, 'isolir'])->name('isolir');
        Route::get('/online', [CustomerController::class, 'online'])->name('online');
        Route::get('/offline', [CustomerController::class, 'offline'])->name('offline');
        Route::get('/canceled', [CustomerController::class, 'canceled'])->name('canceled');
        Route::get('/terminated', [CustomerController::class, 'terminated'])->name('terminated');

        // Aksi Pelanggan Stop Permanen
        Route::post('/{customer}/terminate', [CustomerController::class, 'terminate'])->name('terminate');
        Route::post('/{customer}/reactivate', [CustomerController::class, 'reactivate'])->name('reactivate');
        Route::post('/{customer}/pull-ont', [CustomerController::class, 'pullOnt'])->name('pull-ont');
        Route::post('/{customer}/write-off-invoices', [CustomerController::class, 'writeOffInvoices'])->name('write-off-invoices');
        Route::get('/{customer}/print-termination', [CustomerController::class, 'printTermination'])->name('print-termination');
        Route::post('/{customer}/unsuspend', [CustomerController::class, 'unsuspend'])->name('unsuspend');

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

        // Notifikasi WhatsApp Teknisi Instalasi
        Route::post('/{customer}/notify-technician-wa', [CustomerController::class, 'notifyTechnicianInstallationWa'])
            ->name('notify-technician-wa');
            
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
            
        // Edit Hasil Survey
        Route::post('/{customer}/update-survey', [CustomerController::class, 'updateSurvey'])
            ->name('update-survey');
            
        // Pindah ke tahap Instalasi
        Route::post('/{customer}/mark-installing', [CustomerController::class, 'markInstalling'])
            ->name('mark-installing');
            
        // Batalkan Booking
        Route::post('/{customer}/cancel', [CustomerController::class, 'cancel'])
            ->name('cancel');

        // Pulihkan Pelanggan Dibatalkan (Kembalikan ke Booking)
        Route::post('/{customer}/restore', [CustomerController::class, 'restore'])
            ->name('restore');

        // Pulihkan Massal Pelanggan Dibatalkan
        Route::post('/bulk-restore', [CustomerController::class, 'bulkRestore'])
            ->name('bulk-restore');
            
        // Update ONT via Ajax
        Route::post('/{customer}/update-ont-inline', [CustomerController::class, 'updateOntInline'])
            ->name('update-ont-inline');
        Route::post('/{customer}/material-request', [CustomerController::class, 'requestMaterial'])
            ->name('material-request');
            
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

    // ── Data OLT ──────────────────────────────────────────────
    Route::resource('olts', OltController::class);
    Route::post('olts/{olt}/update', [OltController::class, 'update'])->name('olts.update.post');
    Route::post('olts/{olt}/delete', [OltController::class, 'destroy'])->name('olts.destroy.post');

    // Network Topology & Radar
    Route::get('network-topology', [NetworkTopologyController::class, 'index'])->name('network-topology.index');
    Route::get('network-radar', [\App\Http\Controllers\NetworkRadarController::class, 'index'])->name('network-radar.index');
    Route::post('network-radar/nearby', [\App\Http\Controllers\NetworkRadarController::class, 'nearby'])->name('network-radar.nearby');

    Route::resource('odcs', OdcController::class);
    Route::post('odcs/{odc}/update', [OdcController::class, 'update'])->name('odcs.update.post');
    Route::post('odcs/{odc}/delete', [OdcController::class, 'destroy'])->name('odcs.destroy.post');

    Route::resource('odps', OdpController::class);
    Route::post('odps/{odp}/update', [OdpController::class, 'update'])->name('odps.update.post');
    Route::post('odps/{odp}/delete', [OdpController::class, 'destroy'])->name('odps.destroy.post');

    // ── Rollout & Penugasan Pembangunan ODC & ODP ─────────────
    Route::middleware(['permission:menu_infrastructure_work_orders|menu_network_odc|menu_network_odp'])->prefix('infrastructure/work-orders')->name('infrastructure.work-orders.')->group(function () {
        Route::get('/', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'create'])->name('create');
        Route::get('/api/bom', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'getBOM'])->name('bom');
        Route::post('/', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'store'])->name('store');
        Route::get('/{workOrder}', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'show'])->name('show');
        Route::get('/{workOrder}/print-surat-jalan', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'printSuratJalan'])->name('print-surat-jalan');
        Route::post('/{workOrder}/approve-material', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'approveMaterial'])->name('approve-material');
        Route::post('/{workOrder}/start-work', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'startWork'])->name('start-work');
        Route::post('/{workOrder}/submit-report', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'submitReport'])->name('submit-report');
        Route::post('/{workOrder}/approve', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'approve'])->name('approve');
        Route::post('/{workOrder}/reject', [\App\Http\Controllers\InfrastructureWorkOrderController::class, 'reject'])->name('reject');
    });

    Route::resource('onts', OntController::class);
    Route::post('onts/{ont}/update', [OntController::class, 'update'])->name('onts.update.post');
    Route::post('onts/{ont}/delete', [OntController::class, 'destroy'])->name('onts.destroy.post');
    Route::get('find-onu', [OntController::class, 'findOnu'])->name('find-onu.index');

    Route::resource('materials', MaterialController::class);
    Route::post('employees/bulk-destroy', [\App\Http\Controllers\EmployeeController::class, 'bulkDestroy'])->name('employees.bulk-destroy');
    Route::resource('employees', \App\Http\Controllers\EmployeeController::class);
    Route::post('employees/{employee}/update', [\App\Http\Controllers\EmployeeController::class, 'update'])->name('employees.update.post');
    Route::post('employees/{employee}/delete', [\App\Http\Controllers\EmployeeController::class, 'destroy'])->name('employees.destroy.post');
    Route::resource('positions', \App\Http\Controllers\PositionController::class);
    Route::post('positions/{position}/update', [\App\Http\Controllers\PositionController::class, 'update'])->name('positions.update.post');
    Route::post('positions/{position}/delete', [\App\Http\Controllers\PositionController::class, 'destroy'])->name('positions.destroy.post');
    Route::resource('shifts', \App\Http\Controllers\ShiftController::class);
    Route::post('shifts/{shift}/update', [\App\Http\Controllers\ShiftController::class, 'update'])->name('shifts.update.post');
    Route::post('shifts/{shift}/delete', [\App\Http\Controllers\ShiftController::class, 'destroy'])->name('shifts.destroy.post');
    Route::post('shifts/{shift}/assign', [\App\Http\Controllers\ShiftController::class, 'assignEmployees'])->name('shifts.assign');

    // Jadwal Kerja & Roster Shift
    Route::get('work-schedules', [\App\Http\Controllers\WorkScheduleController::class, 'index'])->name('work-schedules.index');
    Route::post('work-schedules/save-batch', [\App\Http\Controllers\WorkScheduleController::class, 'saveBatch'])->name('work-schedules.save-batch');
    Route::post('work-schedules/generate', [\App\Http\Controllers\WorkScheduleController::class, 'generate'])->name('work-schedules.generate');
    Route::post('work-schedules/continue-previous-month', [\App\Http\Controllers\WorkScheduleController::class, 'continuePreviousMonth'])->name('work-schedules.continue-previous-month');
    Route::post('work-schedules/copy-month', [\App\Http\Controllers\WorkScheduleController::class, 'copyMonth'])->name('work-schedules.copy-month');
    Route::post('work-schedules/reset-month', [\App\Http\Controllers\WorkScheduleController::class, 'resetMonth'])->name('work-schedules.reset-month');
    Route::post('work-schedules/patterns', [\App\Http\Controllers\WorkScheduleController::class, 'storePattern'])->name('work-schedules.patterns.store');
    Route::delete('work-schedules/patterns/{id}', [\App\Http\Controllers\WorkScheduleController::class, 'destroyPattern'])->name('work-schedules.patterns.destroy');
    Route::post('work-schedules/quick-shift', [\App\Http\Controllers\WorkScheduleController::class, 'quickStoreShift'])->name('work-schedules.quick-shift');

    // Absensi Karyawan (Selfie, GPS Radius & Sinkronisasi)
    Route::get('attendances', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendances.index');
    Route::post('attendances/clock-in', [\App\Http\Controllers\AttendanceController::class, 'clockIn'])->name('attendances.clock-in');
    Route::post('attendances/clock-out', [\App\Http\Controllers\AttendanceController::class, 'clockOut'])->name('attendances.clock-out');
    Route::post('attendances/quick-assign-shift', [\App\Http\Controllers\AttendanceController::class, 'quickAssignShift'])->name('attendances.quick-assign-shift');
    Route::post('attendances/update-location', [\App\Http\Controllers\AttendanceController::class, 'updateAreaLocation'])->name('attendances.update-location');

    // Absen Terbaik (Papan Peringkat Kedisiplinan & Penghargaan Juara Top 3)
    Route::get('absen-terbaik', [\App\Http\Controllers\BestAttendanceController::class, 'index'])->name('best-attendances.index');
    Route::get('absen-terbaik/employee-detail/{employee}', [\App\Http\Controllers\BestAttendanceController::class, 'employeeDetail'])->name('best-attendances.employee-detail');
    Route::post('absen-terbaik/sync-payroll', [\App\Http\Controllers\BestAttendanceController::class, 'syncPayroll'])->name('best-attendances.sync-payroll');

    // Absen Tim Khusus (Absensi Lapangan, Tanpa Radius, Grup & Potongan)
    Route::get('absen-khusus', [\App\Http\Controllers\SpecialAttendanceController::class, 'index'])->name('special-attendances.index');
    Route::post('absen-khusus/clock-in', [\App\Http\Controllers\SpecialAttendanceController::class, 'clockIn'])->name('special-attendances.clock-in');
    Route::post('absen-khusus/clock-out', [\App\Http\Controllers\SpecialAttendanceController::class, 'clockOut'])->name('special-attendances.clock-out');
    Route::post('absen-khusus/teams', [\App\Http\Controllers\SpecialAttendanceController::class, 'storeTeam'])->name('special-attendances.teams.store');
    Route::put('absen-khusus/teams/{team}', [\App\Http\Controllers\SpecialAttendanceController::class, 'updateTeam'])->name('special-attendances.teams.update');
    Route::delete('absen-khusus/teams/{team}', [\App\Http\Controllers\SpecialAttendanceController::class, 'destroyTeam'])->name('special-attendances.teams.destroy');
    Route::post('absen-khusus/admin-store', [\App\Http\Controllers\SpecialAttendanceController::class, 'adminStoreAttendance'])->name('special-attendances.admin.store');
    Route::delete('absen-khusus/attendances/{attendance}', [\App\Http\Controllers\SpecialAttendanceController::class, 'adminDeleteAttendance'])->name('special-attendances.admin.destroy');
    Route::post('absen-khusus/sync-payroll', [\App\Http\Controllers\SpecialAttendanceController::class, 'syncToPayroll'])->name('special-attendances.sync-payroll');

    // Absen Tugas Luar (Pengganti Absen Masuk & Pulang Reguler)
    Route::get('absen-tugas-luar', [\App\Http\Controllers\FieldDutyController::class, 'index'])->name('field-duties.index');
    Route::post('absen-tugas-luar', [\App\Http\Controllers\FieldDutyController::class, 'store'])->name('field-duties.store');
    Route::post('absen-tugas-luar/settings', [\App\Http\Controllers\FieldDutyController::class, 'updateSettings'])->name('field-duties.settings.update');
    Route::post('absen-tugas-luar/bulk-delete', [\App\Http\Controllers\FieldDutyController::class, 'bulkDestroy'])->name('field-duties.bulk-delete');
    Route::post('absen-tugas-luar/{fieldDuty}/check-in', [\App\Http\Controllers\FieldDutyController::class, 'checkIn'])->name('field-duties.check-in');
    Route::post('absen-tugas-luar/{fieldDuty}/check-out', [\App\Http\Controllers\FieldDutyController::class, 'checkOut'])->name('field-duties.check-out');
    Route::delete('absen-tugas-luar/{fieldDuty}', [\App\Http\Controllers\FieldDutyController::class, 'destroy'])->name('field-duties.destroy');
    Route::post('materials/bulk-destroy', [MaterialController::class, 'bulkDestroy'])->name('materials.bulk-destroy');
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
    Route::resource('material-transactions', MaterialTransactionController::class)->except(['destroy']);
    Route::post('material-transactions/{item}/register-ont', [MaterialTransactionController::class, 'registerOnt'])->name('material-transactions.register-ont');
    Route::post('material-transactions/{item}/reset-ont', [MaterialTransactionController::class, 'resetOnt'])->name('material-transactions.reset-ont');
    Route::post('material-transactions/{material_transaction}/delete', [MaterialTransactionController::class, 'destroy'])->name('material-transactions.destroy');
    Route::post('material-transactions/{material_transaction}/approve', [MaterialTransactionController::class, 'approveReturn'])->name('material-transactions.approve');
    Route::post('material-transactions/{material_transaction}/reject', [MaterialTransactionController::class, 'rejectReturn'])->name('material-transactions.reject');

    Route::get('material-mutations', [\App\Http\Controllers\MaterialMutationController::class, 'index'])->name('material-mutations.index');

    // ── INVENTORY & LOGISTIK (NEW SYSTEM) ─────────────────────
    Route::resource('produk', \App\Http\Controllers\ProductController::class);
    Route::resource('order-toko', \App\Http\Controllers\PurchaseOrderController::class);
    Route::post('order-toko/{id}/send-ont', [\App\Http\Controllers\PurchaseOrderController::class, 'sendOnt'])->name('order-toko.send-ont');
    Route::resource('stok-in-out', \App\Http\Controllers\StockInOutController::class);
    Route::get('warehouse-stocks', [\App\Http\Controllers\WarehouseStockController::class, 'index'])->name('warehouse-stocks.index');
    Route::delete('warehouse-stocks/{id}', [\App\Http\Controllers\WarehouseStockController::class, 'destroy'])->name('warehouse-stocks.destroy');

    // ── Permintaan Material (Teknisi -> Gudang Utama) ────────────────────


    // ── Data Jaringan (Dashboard Topology) ─────────────────────
    Route::get('/network-data', [\App\Http\Controllers\NetworkDataController::class, 'index'])->name('network-data.index');
    Route::get('/network-data/terminated', [\App\Http\Controllers\NetworkDataController::class, 'terminated'])->name('network-data.terminated');

    // ── Vouchers & Profil Voucher ─────────────────────────────
    Route::get('/vouchers/profiles', [\App\Http\Controllers\VoucherProfileController::class, 'index'])->name('vouchers.profiles.index');
    Route::post('/vouchers/profiles', [\App\Http\Controllers\VoucherProfileController::class, 'store'])->name('vouchers.profiles.store');
    Route::post('/vouchers/profiles/{voucherProfile}/update', [\App\Http\Controllers\VoucherProfileController::class, 'update'])->name('vouchers.profiles.update.post');
    Route::post('/vouchers/profiles/{voucherProfile}/delete', [\App\Http\Controllers\VoucherProfileController::class, 'destroy'])->name('vouchers.profiles.destroy.post');

    Route::get('/vouchers/free', [\App\Http\Controllers\VoucherController::class, 'free'])->name('vouchers.free');
    Route::post('/vouchers/free/customer/{customer}', [\App\Http\Controllers\VoucherController::class, 'generateForCustomer'])->name('vouchers.free.customer');
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
    Route::post('/reseller-settlements/{payment}/cancel', [\App\Http\Controllers\ResellerBillingController::class, 'cancel'])->name('reseller-settlements.cancel');

    Route::get('/reseller-reports', [\App\Http\Controllers\ResellerReportController::class, 'index'])->name('reseller-reports.index');
    Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.destroy');


    // ── Pengguna & Hak Akses ───────────────────────────────────
    Route::middleware(['permission:menu_users'])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class)->except(['create', 'show', 'edit']);
        Route::post('users/{user}/update', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update.post');
        Route::post('users/{user}/delete', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy.post');
    });

    // ── Billing & Keuangan ─────────────────────────────────────
    Route::middleware(['permission:menu_invoices'])->group(function () {
        Route::post('invoices/{invoice}/pay', [\App\Http\Controllers\InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::post('invoices/{invoice}/rollback', [\App\Http\Controllers\InvoiceController::class, 'rollback'])->name('invoices.rollback');
        Route::post('invoices/{invoice}/promise', [\App\Http\Controllers\InvoiceController::class, 'promise'])->name('invoices.promise');
        Route::post('invoices/{invoice}/stop-sementara', [\App\Http\Controllers\InvoiceController::class, 'stopSementara'])->name('invoices.stop-sementara');
        Route::post('invoices/{invoice}/stop-permanen', [\App\Http\Controllers\InvoiceController::class, 'stopPermanen'])->name('invoices.stop-permanen');
        Route::post('invoices/{invoice}/change-package', [\App\Http\Controllers\InvoiceController::class, 'changePackage'])->name('invoices.change-package');
        Route::get('invoices/{invoice}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
        Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    });
    
    // ── Pantauan Pelanggan (Menunggak, Janji Bayar, Stop Sementara) ──────
    Route::middleware(['permission:menu_monitoring_pelanggan|menu_customers_all'])->group(function () {
        Route::get('/monitoring-pelanggan', [\App\Http\Controllers\CustomerMonitoringController::class, 'index'])->name('monitoring-pelanggan.index');
        Route::post('/monitoring-pelanggan/{customer}/unsuspend', [\App\Http\Controllers\CustomerMonitoringController::class, 'unsuspend'])->name('monitoring-pelanggan.unsuspend');
        Route::post('/monitoring-pelanggan/{customer}/suspend', [\App\Http\Controllers\CustomerMonitoringController::class, 'suspend'])->name('monitoring-pelanggan.suspend');
        Route::post('/monitoring-pelanggan/{invoice}/set-promise', [\App\Http\Controllers\CustomerMonitoringController::class, 'setPromise'])->name('monitoring-pelanggan.set-promise');
    });

    // ── Monitoring Kinerja Teknisi (Penanganan Gangguan & Pemasangan Instalasi) ──
    Route::middleware(['permission:menu_monitoring_teknisi|menu_tickets|menu_customers_installed'])->group(function () {
        Route::get('/monitoring-teknisi', [\App\Http\Controllers\TechnicianMonitoringController::class, 'index'])->name('technician-monitoring.index');
        Route::get('/monitoring-teknisi/detail/{technician}', [\App\Http\Controllers\TechnicianMonitoringController::class, 'technicianDetail'])->name('technician-monitoring.detail');
    });

    // ── Customer Billing (Penagihan Lapangan) ─────────────────────
    Route::middleware(['permission:menu_customer_billing'])->group(function () {
        Route::get('/customer-billing', [\App\Http\Controllers\CustomerBillingController::class, 'index'])->name('customer-billing.index');
        Route::post('/customer-billing/{invoice}/collect', [\App\Http\Controllers\CustomerBillingController::class, 'collect'])->name('customer-billing.collect');
        Route::get('/customer-settlements', [\App\Http\Controllers\CustomerBillingController::class, 'settlements'])->name('customer-settlements.index');
        Route::post('/customer-settlements/{payment}/approve', [\App\Http\Controllers\CustomerBillingController::class, 'approve'])->name('customer-settlements.approve');
        Route::post('/customer-settlements/{payment}/cancel', [\App\Http\Controllers\CustomerBillingController::class, 'cancel'])->name('customer-settlements.cancel');
    });

    // ── Laporan Keuangan Global & Pajak ───────────────────────────
    Route::middleware(['permission:menu_financial_reports'])->group(function () {
        Route::get('tax-reports/print', [\App\Http\Controllers\TaxReportController::class, 'print'])->name('tax.reports.print');
        Route::get('tax-reports', [\App\Http\Controllers\TaxReportController::class, 'index'])->name('tax.reports.index');
        Route::get('/financial-reports', [\App\Http\Controllers\FinancialReportController::class, 'index'])->name('financial-reports.index');
        Route::get('/customer-reports', [\App\Http\Controllers\CustomerReportController::class, 'index'])->name('customer-reports.index');
    });

    // ── Ticketing & Gangguan ───────────────────────────────────
    Route::resource('tickets', TicketController::class);

    // ── Penanganan Sigap (PGS) ──────────────────────────────────
    Route::get('pgs', [\App\Http\Controllers\PgsController::class, 'index'])->name('pgs.index');
    Route::get('pgs/jadwal', [\App\Http\Controllers\PgsController::class, 'jadwal'])->name('pgs.jadwal');
    Route::get('pgs/laporan', [\App\Http\Controllers\PgsController::class, 'laporan'])->name('pgs.laporan');
    Route::get('pgs/{pgs}/print', [\App\Http\Controllers\PgsController::class, 'print'])->name('pgs.print');
    Route::post('pgs', [\App\Http\Controllers\PgsController::class, 'store'])->name('pgs.store');
    Route::post('pgs/{pgs}/resolve-admin', [\App\Http\Controllers\PgsController::class, 'resolveByAdmin'])->name('pgs.resolve-admin');
    Route::post('pgs/{pgs}/escalate', [\App\Http\Controllers\PgsController::class, 'escalateToLead'])->name('pgs.escalate');
    Route::post('pgs/{pgs}/schedule', [\App\Http\Controllers\PgsController::class, 'schedule'])->name('pgs.schedule');
    Route::post('pgs/{pgs}/report', [\App\Http\Controllers\PgsController::class, 'submitReport'])->name('pgs.report');
    Route::post('pgs/{pgs}/request-material', [\App\Http\Controllers\PgsController::class, 'requestMaterial'])->name('pgs.request-material');
    Route::post('pgs/{pgs}/material-requests/{materialRequest}/approve', [\App\Http\Controllers\PgsController::class, 'approveMaterialRequest'])->name('pgs.material-requests.approve');
    Route::post('pgs/{pgs}/material-requests/{materialRequest}/reject', [\App\Http\Controllers\PgsController::class, 'rejectMaterialRequest'])->name('pgs.material-requests.reject');
    Route::delete('pgs/{pgs}', [\App\Http\Controllers\PgsController::class, 'destroy'])->name('pgs.destroy');

    // ── Penanganan Gangguan Komplain (PGK) ───────────────────────
    Route::get('pgk', [\App\Http\Controllers\PgkController::class, 'index'])->name('pgk.index');
    Route::get('pgk/jadwal', [\App\Http\Controllers\PgkController::class, 'jadwal'])->name('pgk.jadwal');
    Route::get('pgk/laporan', [\App\Http\Controllers\PgkController::class, 'laporan'])->name('pgk.laporan');
    Route::get('pgk/{pgk}/print', [\App\Http\Controllers\PgkController::class, 'print'])->name('pgk.print');
    Route::post('pgk', [\App\Http\Controllers\PgkController::class, 'store'])->name('pgk.store');
    Route::post('pgk/{pgk}/resolve-admin', [\App\Http\Controllers\PgkController::class, 'resolveByAdmin'])->name('pgk.resolve-admin');
    Route::post('pgk/{pgk}/escalate', [\App\Http\Controllers\PgkController::class, 'escalateToLead'])->name('pgk.escalate');
    Route::post('pgk/{pgk}/schedule', [\App\Http\Controllers\PgkController::class, 'schedule'])->name('pgk.schedule');
    Route::post('pgk/{pgk}/report', [\App\Http\Controllers\PgkController::class, 'submitReport'])->name('pgk.report');
    Route::post('pgk/{pgk}/request-material', [\App\Http\Controllers\PgkController::class, 'requestMaterial'])->name('pgk.request-material');
    Route::post('pgk/{pgk}/material-requests/{materialRequest}/approve', [\App\Http\Controllers\PgkController::class, 'approveMaterialRequest'])->name('pgk.material-requests.approve');
    Route::post('pgk/{pgk}/material-requests/{materialRequest}/reject', [\App\Http\Controllers\PgkController::class, 'rejectMaterialRequest'])->name('pgk.material-requests.reject');
    Route::delete('pgk/{pgk}', [\App\Http\Controllers\PgkController::class, 'destroy'])->name('pgk.destroy');

    // ── Penanganan Gangguan Jalur (PGJ) ──────────────────────────
    Route::get('pgj', [\App\Http\Controllers\PgjController::class, 'index'])->name('pgj.index');
    Route::get('pgj/jadwal', [\App\Http\Controllers\PgjController::class, 'jadwal'])->name('pgj.jadwal');
    Route::get('pgj/laporan', [\App\Http\Controllers\PgjController::class, 'laporan'])->name('pgj.laporan');
    Route::get('pgj/{pgj}/print', [\App\Http\Controllers\PgjController::class, 'print'])->name('pgj.print');
    Route::post('pgj', [\App\Http\Controllers\PgjController::class, 'store'])->name('pgj.store');
    Route::post('pgj/{pgj}/resolve-admin', [\App\Http\Controllers\PgjController::class, 'resolveByAdmin'])->name('pgj.resolve-admin');
    Route::post('pgj/{pgj}/escalate', [\App\Http\Controllers\PgjController::class, 'escalateToLead'])->name('pgj.escalate');
    Route::post('pgj/{pgj}/schedule', [\App\Http\Controllers\PgjController::class, 'schedule'])->name('pgj.schedule');
    Route::post('pgj/{pgj}/report', [\App\Http\Controllers\PgjController::class, 'submitReport'])->name('pgj.report');
    Route::delete('pgj/{pgj}', [\App\Http\Controllers\PgjController::class, 'destroy'])->name('pgj.destroy');
    
    // CBP / Cabut Perangkat
    Route::get('cbp', [\App\Http\Controllers\CbpRequestController::class, 'index'])->name('cbp.index');
    Route::get('cbp/jadwal', [\App\Http\Controllers\CbpRequestController::class, 'jadwal'])->name('cbp.jadwal');
    Route::get('cbp/laporan', [\App\Http\Controllers\CbpRequestController::class, 'laporan'])->name('cbp.laporan');
    Route::post('cbp', [\App\Http\Controllers\CbpRequestController::class, 'store'])->name('cbp.store');
    Route::post('cbp/{cbp}/assign', [\App\Http\Controllers\CbpRequestController::class, 'assign'])->name('cbp.assign');
    Route::post('cbp/{cbp}/status', [\App\Http\Controllers\CbpRequestController::class, 'updateStatus'])->name('cbp.status');
    Route::post('cbp/{cbp}/cancel', [\App\Http\Controllers\CbpRequestController::class, 'cancel'])->name('cbp.cancel');
    // Route::resource('schedules', TechnicianScheduleController::class);

    // ── Penggajian & Insentif (Payroll) ────────────────────────
    Route::middleware(['permission:menu_payroll'])->group(function () {
        Route::get('/payroll', [\App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/settings', [\App\Http\Controllers\PayrollController::class, 'updateSettings'])->name('payroll.settings.update');
        Route::post('/payroll/{user}/incentives', [\App\Http\Controllers\PayrollController::class, 'storeIncentive'])->name('payroll.incentive.store');
        Route::delete('/payroll/incentives/{incentive}', [\App\Http\Controllers\PayrollController::class, 'destroyIncentive'])->name('payroll.incentive.destroy');
        Route::post('/payroll/{user}/deductions', [\App\Http\Controllers\PayrollController::class, 'storeDeduction'])->name('payroll.deduction.store');
        Route::post('/payroll/{user}/disburse', [\App\Http\Controllers\PayrollController::class, 'disburse'])->name('payroll.disburse');
        Route::post('/payroll/disburse-all', [\App\Http\Controllers\PayrollController::class, 'disburseAll'])->name('payroll.disburse-all');
        Route::post('/payroll/{user}/update-bpjs-bank', [\App\Http\Controllers\PayrollController::class, 'updateBpjsBank'])->name('payroll.update-bpjs-bank');

        // Insentif & Potongan List
        Route::get('/insentif-potongan', [\App\Http\Controllers\IncentiveDeductionController::class, 'index'])->name('insentif-potongan.index');
        Route::delete('/insentif-potongan/destroy', [\App\Http\Controllers\IncentiveDeductionController::class, 'destroyRecord'])->name('insentif-potongan.destroy');

        // Bonus Target Market
        Route::get('/bonus-target-market', [\App\Http\Controllers\TargetMarketController::class, 'index'])->name('bonus-target-market.index');
        Route::post('/bonus-target-market/programs', [\App\Http\Controllers\TargetMarketController::class, 'storeProgram'])->name('bonus-target-market.programs.store');
        Route::post('/bonus-target-market/programs/{program}/update', [\App\Http\Controllers\TargetMarketController::class, 'updateProgram'])->name('bonus-target-market.programs.update');
        Route::post('/bonus-target-market/programs/{program}/toggle', [\App\Http\Controllers\TargetMarketController::class, 'toggleProgram'])->name('bonus-target-market.programs.toggle');
        Route::delete('/bonus-target-market/programs/{program}', [\App\Http\Controllers\TargetMarketController::class, 'destroyProgram'])->name('bonus-target-market.programs.destroy');
        Route::post('/bonus-target-market/recalculate', [\App\Http\Controllers\TargetMarketController::class, 'recalculate'])->name('bonus-target-market.recalculate');
        Route::post('/bonus-target-market/sync-payroll', [\App\Http\Controllers\TargetMarketController::class, 'syncToPayroll'])->name('bonus-target-market.sync-payroll');
        
        // Master Insentif & Potongan
        Route::resource('settings/payroll-categories', \App\Http\Controllers\PayrollCategoryController::class);
        Route::get('api/payroll-categories/active', [\App\Http\Controllers\PayrollCategoryController::class, 'getActiveCategories'])->name('payroll-categories.active');

        // Master Fee
        Route::resource('settings/master-fees', \App\Http\Controllers\MasterFeeController::class);
        Route::get('/settings/payroll', [\App\Http\Controllers\SettingController::class, 'payroll'])->name('settings.payroll');
        Route::post('/settings/payroll', [\App\Http\Controllers\SettingController::class, 'updatePayroll'])->name('settings.payroll.update');
        Route::post('/settings/payroll/test-xendit', [\App\Http\Controllers\SettingController::class, 'testXendit'])->name('settings.payroll.test-xendit');
    });

    // ── WhatsApp Gateway & Blast Cerdas ───────────────────────
    Route::middleware(['permission:menu_whatsapp_blast'])->prefix('whatsapp-blast')->name('whatsapp-blast.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WhatsAppBlastController::class, 'index'])->name('index');
        Route::get('/status', [\App\Http\Controllers\WhatsAppBlastController::class, 'getStatus'])->name('status');
        Route::post('/logout', [\App\Http\Controllers\WhatsAppBlastController::class, 'logout'])->name('logout');
        Route::post('/restart', [\App\Http\Controllers\WhatsAppBlastController::class, 'restart'])->name('restart');
        Route::post('/recipients', [\App\Http\Controllers\WhatsAppBlastController::class, 'getRecipients'])->name('recipients');
        Route::post('/campaigns', [\App\Http\Controllers\WhatsAppBlastController::class, 'createCampaign'])->name('campaigns.create');
        Route::post('/campaigns/{campaign}/dispatch', [\App\Http\Controllers\WhatsAppBlastController::class, 'dispatchRecipient'])->name('campaigns.dispatch');
        Route::post('/campaigns/{campaign}/complete', [\App\Http\Controllers\WhatsAppBlastController::class, 'completeCampaign'])->name('campaigns.complete');
        Route::post('/test-send', [\App\Http\Controllers\WhatsAppBlastController::class, 'testSend'])->name('test-send');
        Route::post('/settings', [\App\Http\Controllers\WhatsAppBlastController::class, 'updateSettings'])->name('settings.update');
    });

    // ── Master Area & Pengeluaran ──────────────────────────────
    Route::middleware(['permission:menu_settings_areas'])->group(function () {
        Route::resource('settings/areas', \App\Http\Controllers\AreaController::class);
        Route::post('settings/areas/{area}/update', [\App\Http\Controllers\AreaController::class, 'update'])->name('areas.update.post');
        Route::post('settings/areas/{area}/delete', [\App\Http\Controllers\AreaController::class, 'destroy'])->name('areas.destroy.post');

        // Master Kategori Pengeluaran
        Route::resource('settings/expense-categories', \App\Http\Controllers\ExpenseCategoryController::class);
        Route::post('settings/expense-categories/{expenseCategory}/toggle', [\App\Http\Controllers\ExpenseCategoryController::class, 'toggleStatus'])->name('expense-categories.toggle');
        Route::get('api/expense-categories/active', [\App\Http\Controllers\ExpenseCategoryController::class, 'getActiveCategories'])->name('expense-categories.active');
    });
    
    // ── Pengaturan Branding, Billing & API ─────────────────────
    Route::middleware(['permission:menu_settings_branding|menu_settings_billing|menu_settings_api|menu_settings_roles'])->group(function () {
        Route::get('/settings/branding', [\App\Http\Controllers\SettingController::class, 'branding'])->name('settings.branding');
        Route::post('/settings/branding', [\App\Http\Controllers\SettingController::class, 'updateBranding'])->name('settings.branding.update');
        
        Route::get('/settings/billing', [\App\Http\Controllers\SettingController::class, 'billing'])->name('settings.billing');
        Route::post('/settings/billing', [\App\Http\Controllers\SettingController::class, 'updateBilling'])->name('settings.billing.update');
        Route::get('/settings/invoice-template/preview', [\App\Http\Controllers\SettingController::class, 'previewInvoiceTemplate'])->name('settings.invoice-template.preview');

        Route::get('/settings/attendance', [\App\Http\Controllers\SettingController::class, 'attendance'])->name('settings.attendance');
        Route::post('/settings/attendance', [\App\Http\Controllers\SettingController::class, 'updateAttendance'])->name('settings.attendance.update');

        Route::get('/settings/api', function () {
            return inertia('Settings/Api');
        })->name('settings.api');
        Route::post('/settings/api/test', [\App\Http\Controllers\SettingController::class, 'apiTest'])->name('settings.api.test');
        Route::post('/settings/api/sync', [\App\Http\Controllers\SettingController::class, 'apiSync'])->name('settings.api.sync');
        Route::post('/settings/api/token', function (Illuminate\Http\Request $request) {
            $user = $request->user();
            $user->tokens()->delete(); // Hapus token lama
            $token = $user->createToken('Integrasi-app-LK')->plainTextToken;
            return response()->json(['token' => $token]);
        })->name('settings.api.token');

        Route::get('/settings/resellers', [\App\Http\Controllers\ResellerSettingController::class, 'index'])->name('settings.resellers.index');
        Route::post('/settings/resellers/{user}/update', [\App\Http\Controllers\ResellerSettingController::class, 'update'])->name('settings.resellers.update.post');

        // Data Migration Center
        Route::get('/settings/data-migration', [\App\Http\Controllers\DataMigrationController::class, 'index'])->name('settings.data-migration.index');
        Route::get('/settings/data-migration/template/{category}', [\App\Http\Controllers\DataMigrationController::class, 'downloadTemplate'])->name('settings.data-migration.template');
        Route::post('/settings/data-migration/dry-run', [\App\Http\Controllers\DataMigrationController::class, 'dryRun'])->name('settings.data-migration.dry-run');
        Route::post('/settings/data-migration/execute', [\App\Http\Controllers\DataMigrationController::class, 'execute'])->name('settings.data-migration.execute');
        Route::post('/settings/data-migration/error-report', [\App\Http\Controllers\DataMigrationController::class, 'downloadErrorReport'])->name('settings.data-migration.error-report');
    });

    // ── Manajemen Role & Hak Akses ─────────────────────────────
    Route::middleware(['permission:menu_settings_roles'])->group(function () {
        Route::resource('settings/roles', \App\Http\Controllers\RoleController::class)->except(['create', 'show', 'edit']);
        Route::post('settings/roles/{role}/update', [\App\Http\Controllers\RoleController::class, 'update'])->name('roles.update.post');
        Route::post('settings/roles/{role}/clone', [\App\Http\Controllers\RoleController::class, 'clone'])->name('roles.clone');
        Route::post('settings/roles/{role}/apply-preset', [\App\Http\Controllers\RoleController::class, 'applyPreset'])->name('roles.apply-preset');
        Route::post('settings/roles/{role}/delete', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy.post');
    });

    Route::resource('internet-packages', \App\Http\Controllers\InternetPackageController::class)->except(['create', 'show', 'edit']);
    Route::post('internet-packages/{internet_package}/update', [\App\Http\Controllers\InternetPackageController::class, 'update'])->name('internet-packages.update.post');
    Route::post('internet-packages/{internet_package}/delete', [\App\Http\Controllers\InternetPackageController::class, 'destroy'])->name('internet-packages.destroy.post');

    // ── Data Router & RADIUS ───────────────────────────────────
    Route::middleware(['permission:menu_routers|menu_radius_nas'])->group(function () {
        Route::resource('routers', \App\Http\Controllers\RouterController::class)->except(['create', 'show', 'edit']);
        Route::post('routers/{router}/update', [\App\Http\Controllers\RouterController::class, 'update'])->name('routers.update.post');
        Route::post('routers/{router}/delete', [\App\Http\Controllers\RouterController::class, 'destroy'])->name('routers.destroy.post');
        Route::get('routers/{router}/ping', [\App\Http\Controllers\RouterController::class, 'ping'])->name('routers.ping');

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

    // ── Portal Khusus Mitra Afiliasi & Sales ────────────────────
    Route::prefix('affiliate')->name('affiliate.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\AffiliatePortalController::class, 'dashboard'])->name('dashboard');
        Route::post('/bank', [\App\Http\Controllers\AffiliatePortalController::class, 'updateBank'])->name('bank');
        Route::post('/withdraw', [\App\Http\Controllers\AffiliatePortalController::class, 'withdraw'])->name('withdraw');
    });

    // ── Manajemen Afiliasi & Pengaturan Komisi (Admin) ─────────
    Route::middleware(['permission:menu_marketing_affiliates'])->prefix('marketing/affiliates')->name('admin.affiliates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AffiliateManagementController::class, 'index'])->name('index');
        Route::post('/settings', [\App\Http\Controllers\Admin\AffiliateManagementController::class, 'saveSettings'])->name('settings');
        Route::post('/withdrawals/{withdrawal}/approve', [\App\Http\Controllers\Admin\AffiliateManagementController::class, 'approveWithdrawal'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [\App\Http\Controllers\Admin\AffiliateManagementController::class, 'rejectWithdrawal'])->name('withdrawals.reject');
        Route::post('/{affiliate}/toggle-status', [\App\Http\Controllers\Admin\AffiliateManagementController::class, 'toggleStatus'])->name('toggle-status');
    });
});
