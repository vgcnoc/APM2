<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CbpRequest;
use App\Models\User;
use App\Services\DataScopeService;
use Carbon\Carbon;
use Inertia\Inertia;

class CbpRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_cbp')) {
            if ($user->can('menu_cbp_laporan')) {
                return redirect()->route('cbp.laporan');
            }
            if ($user->can('menu_cbp_jadwal')) {
                return redirect()->route('cbp.jadwal');
            }
            abort(403, 'Akses ke modul CBP tidak diizinkan.');
        }

        // Base query untuk statistik cards
        $baseCountQuery = CbpRequest::query();
        DataScopeService::applyCbpScope($baseCountQuery);

        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'not_sent' => (clone $baseCountQuery)->where('status', 'pending')->whereNull('sent_to_schedule_at')->count(),
            'sent_to_schedule' => (clone $baseCountQuery)->where('status', 'pending')->whereNotNull('sent_to_schedule_at')->count(),
            'assigned' => (clone $baseCountQuery)->where('status', 'assigned')->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
            'canceled' => (clone $baseCountQuery)->where('status', 'canceled')->count(),
        ];

        $query = CbpRequest::with(['customer.areaModel', 'customer.package', 'customer.ont.odp', 'technicians', 'creator', 'dispatcher', 'auditLogs']);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyCbpScope($query);

        // Filter status
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'not_sent') {
                $query->where('status', 'pending')->whereNull('sent_to_schedule_at');
            } elseif ($request->status === 'sent_to_schedule') {
                $query->where('status', 'pending')->whereNotNull('sent_to_schedule_at');
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter Area
        if ($request->filled('area_id') && $request->area_id !== 'all') {
            $query->whereHas('customer', function($cq) use ($request) {
                $cq->where('area_id', $request->area_id);
            });
        }

        // Filter Periode
        if ($request->filled('period') && $request->period !== 'all') {
            if ($request->period === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($request->period === 'this_week') {
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($request->period === 'this_month') {
                $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            } elseif ($request->period === 'last_month') {
                $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
            } elseif ($request->period === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }
        }

        // Search komprehensif
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('cbp_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('dispatch_notes', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('ont', function($oq) use ($search) {
                            $oq->where('serial_number', 'like', "%{$search}%")
                               ->orWhereHas('odp', fn($odpq) => $odpq->where('name', 'like', "%{$search}%"));
                        });
                  });
            });
        }

        $cbpRequests = $query->latest('id')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        $areas = DataScopeService::getScopedAreas($user, 'cbp');
        $customerQuery = \App\Models\Customer::where('status', 'active')
            ->select('id', 'name', 'customer_code', 'area_id', 'phone', 'address', 'status', 'service_status', 'package_id')
            ->with('package:id,name');
        DataScopeService::applyCustomerScope($customerQuery, $user);
        $customers = $customerQuery->get();

        return Inertia::render('Tickets/Cbp', [
            'requests' => $cbpRequests,
            'counts' => $counts,
            'areas' => $areas,
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'area_id', 'period', 'start_date', 'end_date', 'per_page']),
        ]);
    }

    public function jadwal(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_cbp_jadwal')) {
            if ($user->can('menu_cbp_laporan')) {
                return redirect()->route('cbp.laporan');
            }
            if ($user->can('menu_cbp')) {
                return redirect()->route('cbp.index');
            }
            abort(403, 'Akses ke jadwal CBP tidak diizinkan.');
        }

        $baseJadwalCountQuery = CbpRequest::query()->whereNotNull('sent_to_schedule_at');
        DataScopeService::applyCbpScope($baseJadwalCountQuery);

        $completedTodayQuery = CbpRequest::query()
            ->whereNotNull('sent_to_schedule_at')
            ->where('status', 'completed')
            ->whereDate('completed_at', today());
        DataScopeService::applyCbpScope($completedTodayQuery, $user);

        $todayStr = today()->toDateString();
        $overdueCount = (clone $baseJadwalCountQuery)
            ->whereIn('status', ['pending', 'assigned'])
            ->whereNotNull('scheduled_date')
            ->whereDate('scheduled_date', '<', $todayStr)
            ->count();

        $rescheduledCount = (clone $baseJadwalCountQuery)
            ->whereIn('status', ['pending', 'assigned'])
            ->where('reschedule_count', '>', 0)
            ->count();

        $jadwalCounts = [
            'all' => (clone $baseJadwalCountQuery)->whereIn('status', ['pending', 'assigned'])->count(),
            'pending' => (clone $baseJadwalCountQuery)->where('status', 'pending')->count(),
            'assigned' => (clone $baseJadwalCountQuery)->where('status', 'assigned')->count(),
            'overdue' => $overdueCount,
            'rescheduled' => $rescheduledCount,
            'completed_today' => $completedTodayQuery->count(),
        ];

        $query = CbpRequest::with([
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'technicians',
            'creator',
            'dispatcher',
            'rescheduler',
            'auditLogs.user',
            'materialReturns.items.material',
            'materialReturns.receiver',
            'materialReturns.oldOnt'
        ])
            ->whereNotNull('sent_to_schedule_at');

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyCbpScope($query);

        // Filter status jadwal
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'overdue') {
                $query->whereIn('status', ['pending', 'assigned'])
                    ->whereNotNull('scheduled_date')
                    ->whereDate('scheduled_date', '<', $todayStr);
            } elseif ($request->status === 'rescheduled') {
                $query->whereIn('status', ['pending', 'assigned'])
                    ->where('reschedule_count', '>', 0);
            } elseif ($request->status === 'completed_today') {
                $query->where('status', 'completed')
                    ->whereDate('completed_at', today());
            } elseif ($request->status === 'completed') {
                $query->where('status', 'completed');
            } else {
                $query->where('status', $request->status);
            }
        } else {
            $query->whereIn('status', ['pending', 'assigned']);
        }

        // Filter Area
        if ($request->filled('area_id') && $request->area_id !== 'all') {
            $query->whereHas('customer', function($cq) use ($request) {
                $cq->where('area_id', $request->area_id);
            });
        }

        if ($request->filled('scheduled_date')) {
            $query->whereDate('scheduled_date', $request->scheduled_date);
        }

        if ($request->filled('search')) {
            $rawSearch = trim((string) $request->search);
            $search = strtolower($rawSearch);
            $digitsOnly = preg_replace('/\D/', '', $rawSearch);
            $cleanAlnum = preg_replace('/[^a-zA-Z0-9]/', '', $rawSearch);
            $phoneVariants = [];
            if (strlen($digitsOnly) >= 5) {
                $phoneVariants[] = $digitsOnly;
                if (str_starts_with($digitsOnly, '0')) {
                    $phoneVariants[] = '62' . substr($digitsOnly, 1);
                } elseif (str_starts_with($digitsOnly, '62')) {
                    $phoneVariants[] = '0' . substr($digitsOnly, 2);
                }
            }

            $query->where(function($q) use ($search, $phoneVariants, $cleanAlnum) {
                // 1. Nomor Tiket CBP, Alasan, Disposisi
                $q->where('cbp_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('dispatch_notes', 'like', "%{$search}%");

                // 2. Teknisi Lapangan yang ditugaskan
                $q->orWhereHas('technicians', function($tq) use ($search) {
                    $tq->where('name', 'like', "%{$search}%");
                });

                // 3. Pelanggan & Jaringan (Nama, Kode, HP, Alamat, ODP, ONT SN/MAC)
                $q->orWhereHas('customer', function($cq) use ($search, $phoneVariants, $cleanAlnum) {
                    $cq->where('name', 'like', "%{$search}%")
                      ->orWhere('customer_code', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");

                    foreach ($phoneVariants as $pv) {
                        $cq->orWhere('phone', 'like', "%{$pv}%");
                    }

                    // ODP dari ONT pelanggan & survey
                    $cq->orWhereHas('ont.odp', function($oq) use ($search) {
                        $oq->where('name', 'like', "%{$search}%")
                           ->orWhere('address', 'like', "%{$search}%");
                    });

                    // ONT Serial Number, MAC Address, PPPoE User
                    $cq->orWhereHas('ont', function($ontQ) use ($search, $cleanAlnum) {
                        $ontQ->where('serial_number', 'like', "%{$search}%")
                             ->orWhere('mac_address', 'like', "%{$search}%")
                             ->orWhere('pppoe_user', 'like', "%{$search}%");
                        if (strlen($cleanAlnum) >= 4) {
                            $ontQ->orWhereRaw("REPLACE(REPLACE(serial_number, ':', ''), '-', '') LIKE ?", ["%{$cleanAlnum}%"])
                                 ->orWhereRaw("REPLACE(REPLACE(mac_address, ':', ''), '-', '') LIKE ?", ["%{$cleanAlnum}%"]);
                        }
                    });

                    // Wilayah / Area Pelanggan
                    $cq->orWhereHas('areaModel', function($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%");
                    });
                });
            });
        }

        $cbpRequests = $query
            ->orderByRaw('CASE WHEN scheduled_date IS NULL THEN 1 ELSE 0 END ASC, scheduled_date ASC, sent_to_schedule_at DESC')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        $technicians = \App\Models\User::forAssignment()->where(function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
                  ->orWhere('name', 'like', '%teknisi%')
                  ->orWhere('name', 'like', '%serbu%');
            })
            ->orWhereIn('role', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
            ->orWhere('role', 'like', '%teknisi%')
            ->orWhere('role', 'like', '%serbu%');
        })->orderBy('name')->get();
        $areas = DataScopeService::getScopedAreas($user, 'cbp');

        return Inertia::render('Tickets/CbpJadwal', [
            'requests' => $cbpRequests,
            'counts' => $jadwalCounts,
            'technicians' => $technicians,
            'areas' => $areas,
            'filters' => $request->only(['search', 'status', 'area_id', 'scheduled_date', 'per_page']),
        ]);
    }

    public function laporan(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_cbp_laporan')) {
            if ($user->can('menu_cbp')) {
                return redirect()->route('cbp.index');
            }
            if ($user->can('menu_cbp_jadwal')) {
                return redirect()->route('cbp.jadwal');
            }
            abort(403, 'Akses ke laporan CBP tidak diizinkan.');
        }

        $baseQuery = CbpRequest::whereIn('status', ['assigned', 'completed']);
        DataScopeService::applyCbpScope($baseQuery);

        $laporanCounts = [
            'all' => (clone $baseQuery)->count(),
            'assigned' => (clone $baseQuery)->where('status', 'assigned')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
        ];

        $query = CbpRequest::with([
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'customer.technicianSchedules',
            'customer.installedMaterials.material',
            'technicians',
            'creator',
            'auditLogs.user',
            'materialReturns.items.material',
            'materialReturns.receiver',
            'materialReturns.oldOnt'
        ])->whereIn('status', ['assigned', 'completed']);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyCbpScope($query);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('area_id') && $request->area_id !== 'all') {
            $query->whereHas('customer', function($cq) use ($request) {
                $cq->where('area_id', $request->area_id);
            });
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('cbp_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('dispatch_notes', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                  });
            });
        }

        $cbpRequests = $query->latest('id')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        $materials = \App\Models\Material::with('stocks')->where(function ($q) { $q->where('is_active', true)->orWhereNull('is_active'); })->orderBy('name')->get();
        $areas = DataScopeService::getScopedAreas($user, 'cbp');

        return Inertia::render('Tickets/CbpLaporan', [
            'requests' => $cbpRequests,
            'counts' => $laporanCounts,
            'materials' => $materials,
            'areas' => $areas,
            'filters' => $request->only(['search', 'status', 'area_id', 'per_page']),
        ]);
    }

    public function store(Request $request, \App\Services\RadiusService $radius)
    {
        $user = $request->user();
        $canRequest = $user && (
            $user->can('cbp_btn_request') ||
            $user->can('cbp_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'cs', 'admin cs']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'cs', 'admin cs'])
        );
        if (!$canRequest) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat permintaan CBP.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
        ]);

        $customer = \App\Models\Customer::findOrFail($validated['customer_id']);

        // Pastikan pelanggan benar-benar memiliki perangkat fisik / pernah terpasang
        $hasEquipment = $customer->ont()->exists()
            || $customer->onts()->exists()
            || !empty($customer->activation_date)
            || $customer->activeInstalledMaterials()->exists()
            || $customer->installedMaterials()->exists()
            || $customer->technicianSchedules()->where('type', 'installation')->where('status', 'completed')->exists();

        if (!$hasEquipment) {
            return redirect()->back()->with('error', 'Pelanggan ' . $customer->name . ' (' . $customer->customer_code . ') belum pernah dipasang perangkat (tidak ada ONT / aktivasi). Tiket Cabut Perangkat tidak dapat dibuat.');
        }

        // Stop Permanen the customer
        if ($customer->status !== 'terminated') {
            \App\Services\AffiliateService::handleEarlyTerminationPenalty($customer, now()->toDateString());
            $customer->update(['status' => 'terminated']);
            $radius->guard(fn($r) => $r->syncCustomer($customer));
            $accounts = $radius->customerAccounts($customer);
            foreach (array_keys($accounts) as $username) {
                $radius->guard(fn($r) => $r->disconnect($username));
            }
        }

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';
        if (empty($validated['scheduled_date'])) {
            $validated['scheduled_date'] = now()->toDateString();
        }

        $cbp = CbpRequest::create($validated);
        
        \App\Models\AuditLog::createLog('Buat Request CBP', $cbp, null, 'pending', 'Membuat request pencabutan perangkat untuk pelanggan ID: ' . $validated['customer_id'] . ' (Tgl Rencana: ' . $validated['scheduled_date'] . ')');

        return redirect()->back()->with('success', 'Data Pencabutan berhasil diekskalasi dan Pelanggan sudah di-Stop Permanen.');
    }

    public function assign(Request $request, CbpRequest $cbp)
    {
        $user = $request->user();
        $canAssign = $user && (
            $user->can('cbp_btn_assign') ||
            $user->can('cbp_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'dispatcher', 'maintenance', 'maintenance-1']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'dispatcher', 'maintenance', 'maintenance-1'])
        );
        if (!$canAssign) {
            abort(403, 'Anda tidak memiliki hak akses untuk menugaskan teknisi CBP.');
        }

        $validated = $request->validate([
            'technicians' => 'required|array',
            'technicians.*' => 'exists:users,id',
            'notes' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
        ]);

        $oldStatus = $cbp->status;
        $cbpData = ['status' => 'assigned'];
        if ($request->has('notes')) {
            $cbpData['notes'] = $validated['notes'];
        }
        if ($request->filled('scheduled_date')) {
            $cbpData['scheduled_date'] = $validated['scheduled_date'];
        } elseif (!$cbp->scheduled_date) {
            $cbpData['scheduled_date'] = now()->toDateString();
        }

        $cbp->update($cbpData);
        $cbp->technicians()->sync($validated['technicians']);

        $dateLog = !empty($cbpData['scheduled_date']) ? ' Tanggal Rencana Cabut: ' . $cbpData['scheduled_date'] : '';
        $noteLog = !empty($validated['notes']) ? ' Catatan: ' . $validated['notes'] : '';
        \App\Models\AuditLog::createLog('Penugasan Teknisi CBP', $cbp, $oldStatus, 'assigned', 'Menugaskan teknisi untuk tugas CBP.' . $dateLog . $noteLog);

        return redirect()->back()->with('success', 'Tugas Pencabutan berhasil ditugaskan ke Teknisi.');
    }

    public function reschedule(Request $request, CbpRequest $cbp)
    {
        $user = $request->user();
        $canReschedule = $user && (
            $user->can('cbp_btn_reschedule') ||
            $user->can('cbp_btn_assign') ||
            $user->can('cbp_btn_dispatch') ||
            $user->can('cbp_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'dispatcher', 'maintenance', 'maintenance-1', 'cs', 'admin cs']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'dispatcher', 'maintenance', 'maintenance-1', 'cs', 'admin cs'])
        );
        if (!$canReschedule) {
            abort(403, 'Anda tidak memiliki hak akses untuk menjadwalkan ulang (reschedule) CBP.');
        }

        if ($cbp->status === 'completed') {
            return redirect()->back()->with('error', 'Tiket CBP yang sudah selesai tidak dapat dijadwalkan ulang.');
        }

        if ($cbp->status === 'canceled') {
            return redirect()->back()->with('error', 'Tiket CBP yang dibatalkan tidak dapat dijadwalkan ulang.');
        }

        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'reschedule_reason' => 'required|string|max:1000',
            'technician_mode' => 'nullable|string|in:keep,change,reset',
            'technicians' => 'nullable|array',
            'technicians.*' => 'exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $oldDate = $cbp->scheduled_date ? Carbon::parse($cbp->scheduled_date)->format('d/m/Y') : 'Belum Ditentukan';
        $newDateObj = Carbon::parse($validated['scheduled_date']);
        $newDate = $newDateObj->format('d/m/Y');
        $oldStatus = $cbp->status;

        $updateData = [
            'scheduled_date' => $newDateObj->toDateString(),
            'rescheduled_at' => now(),
            'rescheduled_by' => $user->id,
            'reschedule_reason' => $validated['reschedule_reason'],
            'reschedule_count' => ($cbp->reschedule_count ?? 0) + 1,
        ];

        if (!empty($validated['notes'])) {
            $updateData['notes'] = $validated['notes'];
        }

        $techMode = $validated['technician_mode'] ?? 'keep';
        $techLog = '';
        if ($techMode === 'reset') {
            $cbp->technicians()->detach();
            $updateData['status'] = 'pending';
            $updateData['assigned_to'] = null;
            $techLog = ' (Teknisi di-reset menjadi Belum Ditugaskan)';
        } elseif ($techMode === 'change') {
            $techIds = $validated['technicians'] ?? [];
            $cbp->technicians()->sync($techIds);
            if (!empty($techIds)) {
                $updateData['status'] = 'assigned';
                $updateData['assigned_to'] = $techIds[0];
                $techNames = User::whereIn('id', $techIds)->pluck('name')->implode(', ');
                $techLog = " (Teknisi baru: {$techNames})";
            } else {
                $updateData['status'] = 'pending';
                $updateData['assigned_to'] = null;
                $techLog = ' (Teknisi dikosongkan / Belum Ditugaskan)';
            }
        } else {
            $techNames = $cbp->technicians()->pluck('name')->implode(', ');
            $techLog = $techNames ? " (Teknisi dipertahankan: {$techNames})" : " (Status teknisi: Belum Ditugaskan)";
        }

        if (!$cbp->sent_to_schedule_at) {
            $updateData['sent_to_schedule_at'] = now();
            $updateData['sent_to_schedule_by'] = $user->id;
        }

        $cbp->update($updateData);

        \App\Models\AuditLog::createLog(
            'Jadwal Ulang CBP (Reschedule)',
            $cbp,
            $oldStatus,
            $cbp->status,
            "Reschedule pencabutan dari {$oldDate} menjadi {$newDate}. Alasan: {$validated['reschedule_reason']}." . $techLog . (!empty($validated['notes']) ? " Catatan: {$validated['notes']}" : '')
        );

        return redirect()->back()->with('success', "Tiket {$cbp->cbp_number} ({$cbp->customer?->name}) berhasil di-reschedule ke tanggal {$newDate}.");
    }

    public function updateStatus(Request $request, CbpRequest $cbp)
    {
        $user = $request->user();
        $canReport = $user && (
            $user->can('cbp_btn_report') ||
            $user->can('cbp_manage') ||
            $user->hasAnyRole(['admin', 'kepala teknisi', 'teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance']) ||
            str_contains(strtolower($user->role ?? ''), 'teknisi') ||
            str_contains(strtolower($user->role ?? ''), 'serbu')
        );
        if (!$canReport) {
            abort(403, 'Anda tidak memiliki hak akses untuk melaporkan hasil CBP.');
        }

        // Jika scope 'own' (teknisi biasa), verifikasi bahwa user memang ditugaskan pada CBP ini
        if ($user && \App\Services\DataScopeService::getScopeLevel($user, 'cbp') === 'own') {
            $isAssigned = $cbp->technicians()->where('users.id', $user->id)->exists()
                || $cbp->created_by === $user->id
                || $cbp->assigned_to === $user->id;
            if (!$isAssigned) {
                abort(403, 'Anda hanya dapat melaporkan tugas CBP yang ditugaskan kepada Anda.');
            }
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'photo' => 'nullable',
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|file|image|max:10240',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'nullable',
            'materials.*.quantity' => 'required|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string',
            'materials.*.condition' => 'nullable|string|in:Layak Pakai,Rusak,Tidak Layak',
            'materials.*.type' => 'nullable|string',
            'materials.*.name' => 'nullable|string',
            'materials.*.ont_id' => 'nullable',
        ]);

        $uploadedPhotoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                if ($file && $file->isValid()) {
                    $uploadedPhotoPaths[] = $file->store('cbp_photos', 'public');
                }
            }
        }
        if ($request->hasFile('photo')) {
            $uploadedPhotoPaths[] = $request->file('photo')->store('cbp_photos', 'public');
        }

        // Ambil existing photos jika ada
        $existingPhotos = is_array($cbp->photos) ? $cbp->photos : (!empty($cbp->photo) ? [$cbp->photo] : []);
        $allPhotos = array_values(array_unique(array_filter(array_merge($existingPhotos, $uploadedPhotoPaths))));
        $primaryPhoto = $allPhotos[0] ?? $cbp->photo;

        $returnItems = [];
        $uninstalledOnt = null;
        $ontLog = '';

        if ($cbp->status !== 'completed') {
            $validated['completed_at'] = now();
            $customer = $cbp->customer;
            $customer?->loadMissing('ont');

            // 1. Cari semua ONT milik pelanggan ini (bisa dari relasi, customer_id, atau item form)
            $customerOnts = collect();
            if ($customer && $customer->ont) {
                $customerOnts->push($customer->ont);
            }
            if ($cbp->customer_id) {
                $otherOnts = \App\Models\Ont::where('customer_id', $cbp->customer_id)->get();
                foreach ($otherOnts as $o) {
                    if (!$customerOnts->contains('id', $o->id)) {
                        $customerOnts->push($o);
                    }
                }
            }

            // Periksa juga jika ada ont_id di tabel materials form
            $formMaterials = collect($validated['materials'] ?? []);
            foreach ($formMaterials as $m) {
                if (!empty($m['ont_id'])) {
                    $formOnt = \App\Models\Ont::find($m['ont_id']);
                    if ($formOnt && !$customerOnts->contains('id', $formOnt->id)) {
                        $customerOnts->push($formOnt);
                    }
                }
            }

            $affectedOdpIds = collect();

            // Lepas seluruh ONT pelanggan & bebaskan port ODP
            foreach ($customerOnts as $cOnt) {
                if (!$uninstalledOnt) {
                    $uninstalledOnt = $cOnt;
                }

                // Tentukan kondisi ONT dari form jika ada
                $matchingMaterial = $formMaterials->first(function ($m) use ($cOnt) {
                    $isOntType = strtolower($m['type'] ?? '') === 'ont' || str_contains(strtolower($m['name'] ?? ''), 'ont') || str_contains(strtolower($m['name'] ?? ''), 'modem');
                    if (!empty($m['ont_id']) && (int) $m['ont_id'] === (int) $cOnt->id) return true;
                    if (!empty($m['serial_number']) && $m['serial_number'] === $cOnt->serial_number) return true;
                    return $isOntType;
                });

                $ontCond = $matchingMaterial['condition'] ?? 'Layak Pakai';
                $isRusak = in_array(strtolower($ontCond), ['rusak', 'tidak layak', 'damaged']);
                $newOntStatus = $isRusak ? 'rusak' : 'Belum Set/Baru Input';

                $oldOdpId = $cOnt->odp_id;
                $oldPortNum = $cOnt->port_number;
                $oldOdpPortId = $cOnt->odp_port_id;

                if ($oldOdpId) {
                    $affectedOdpIds->push($oldOdpId);
                }

                // Bebaskan port ODP di tabel odp_ports
                if ($oldOdpPortId) {
                    \App\Models\OdpPort::where('id', $oldOdpPortId)->update([
                        'status' => 'available',
                        'description' => null,
                    ]);
                }
                if ($oldOdpId && $oldPortNum) {
                    \App\Models\OdpPort::where('odp_id', $oldOdpId)
                        ->where('port_number', $oldPortNum)
                        ->update([
                            'status' => 'available',
                            'description' => null,
                        ]);
                }

                // Lepas ONT dari pelanggan dan alokasikan ke gudang
                $cOnt->update([
                    'customer_id' => null,
                    'odp_id' => null,
                    'odp_port_id' => null,
                    'port_number' => null,
                    'rx_power' => null,
                    'status' => $newOntStatus,
                    'description' => "Dicabut dari pelanggan " . ($customer?->name ?? "ID #{$cbp->customer_id}") . " via CBP #{$cbp->cbp_number} tgl " . now()->format('d/m/Y H:i') . " [Kondisi: {$ontCond}]. Menunggu serah terima gudang.",
                ]);

                // Update CustomerInstalledMaterial untuk ONT jika ada
                \App\Models\CustomerInstalledMaterial::where('customer_id', $cbp->customer_id)
                    ->where(function ($q) use ($cOnt) {
                        $q->where('item_name', 'like', '%ont%')
                          ->orWhere('item_name', 'like', '%modem%')
                          ->orWhere('serial_number', $cOnt->serial_number);
                    })
                    ->update([
                        'status' => 'dismantled',
                        'notes' => "Dicabut pada tiket CBP #{$cbp->cbp_number}",
                    ]);

                $ontLog .= " Perangkat ONT {$cOnt->brand} {$cOnt->model} (SN: {$cOnt->serial_number}) dicabut [Kondisi: {$ontCond}] & port ODP dibebaskan.";
            }

            // Periksa juga jika ada reservasi survey ODP yang belum terlepas
            if ($cbp->customer_id) {
                $surveys = \App\Models\Survey::where('customer_id', $cbp->customer_id)->whereNotNull('odp_id')->get();
                foreach ($surveys as $survey) {
                    if ($survey->odp_id) {
                        $affectedOdpIds->push($survey->odp_id);
                        if (!empty($survey->port_number)) {
                            \App\Models\OdpPort::where('odp_id', $survey->odp_id)
                                ->where('port_number', $survey->port_number)
                                ->update(['status' => 'available', 'description' => null]);
                        }
                    }
                }
            }

            // Sinkronkan total used_ports & status ODP untuk seluruh ODP yang terpengaruh
            foreach ($affectedOdpIds->unique()->filter() as $odpId) {
                $odp = \App\Models\Odp::find($odpId);
                if ($odp) {
                    $usedCount = \App\Models\Ont::where('odp_id', $odp->id)
                        ->whereNotIn('status', ['in_stock', 'damaged', 'maintenance', 'returned'])
                        ->count();
                    $newStatus = ($odp->total_ports > 0 && $usedCount >= $odp->total_ports) ? 'full' : 'active';
                    $odp->update([
                        'used_ports' => $usedCount,
                        'status' => $newStatus,
                    ]);
                }
            }

            // 2. Handle Returned Materials via Serah Terima Gudang (MaterialHandoverService)
            // Pastikan jika ada ONT yang dicabut tapi belum dimasukkan teknisi ke tabel materials, otomatis ditambahkan
            $hasOntInMaterials = $formMaterials->contains(function ($m) {
                $type = strtolower($m['type'] ?? '');
                $name = strtolower($m['name'] ?? '');
                return $type === 'ont' || str_contains($name, 'ont') || str_contains($name, 'modem');
            });

            if (!$hasOntInMaterials && $customerOnts->isNotEmpty()) {
                foreach ($customerOnts as $cOnt) {
                    $formMaterials->push([
                        'material_id' => null,
                        'name' => "ONT {$cOnt->brand} {$cOnt->model} (SN: {$cOnt->serial_number})",
                        'quantity' => 1,
                        'unit' => 'unit',
                        'condition' => 'Layak Pakai',
                        'type' => 'ont',
                        'ont_id' => $cOnt->id,
                        'serial_number' => $cOnt->serial_number,
                    ]);
                }
            }

            if ($formMaterials->isNotEmpty()) {
                foreach ($formMaterials as $item) {
                    $materialId = $item['material_id'] ?? null;
                    $material = $materialId ? \App\Models\Material::find($materialId) : null;
                    $itemName = $item['name'] ?? ($material?->name ?? 'Material Cabutan');
                    $cond = $item['condition'] ?? 'Layak Pakai';
                    $qty = (float) ($item['quantity'] ?? 1);
                    $unit = $item['unit'] ?? ($material?->unit ?? 'pcs');

                    // Jika item ONT, pastikan nama dan SN tercatat rapi
                    $isOntType = strtolower($item['type'] ?? '') === 'ont' || str_contains(strtolower($itemName), 'ont');
                    if ($isOntType) {
                        if ($uninstalledOnt) {
                            $itemName = "ONT {$uninstalledOnt->brand} {$uninstalledOnt->model} (SN: {$uninstalledOnt->serial_number})";
                            $resolved = \App\Services\MaterialService::resolveOntMaterial($uninstalledOnt);
                            if ($resolved) {
                                $material = $resolved;
                            }
                        }

                        // Jika material belum ditemukan atau salah terpetakan ke ML212 (ID 186) padahal nama item menyebut merk lain
                        $isWrongML212 = ($material && $material->id == 186 && !str_contains(strtolower($itemName), 'ml212'));
                        if (!$material || $isWrongML212) {
                            // Cek apakah ada record di customer_installed_materials
                            $custInstalledOnt = \App\Models\CustomerInstalledMaterial::where('customer_id', $cbp->customer_id)
                                ->where(function ($q) {
                                    $q->where('item_name', 'like', '%ont%')
                                      ->orWhere('item_name', 'like', '%modem%')
                                      ->orWhere('item_name', 'like', '%zte%')
                                      ->orWhere('item_name', 'like', '%huawei%');
                                })
                                ->where('status', '!=', 'dismantled')
                                ->first();

                            if ($custInstalledOnt && $custInstalledOnt->material_id) {
                                $installedMat = \App\Models\Material::find($custInstalledOnt->material_id);
                                if ($installedMat) {
                                    $material = $installedMat;
                                }
                            }
                        }

                        // Jika masih belum cocok, coba resolusi cerdas berdasarkan nama item
                        if (!$material || ($material->id == 186 && !str_contains(strtolower($itemName), 'ml212'))) {
                            $matchedMat = self::resolveOntMaterialByName($itemName);
                            if ($matchedMat) {
                                $material = $matchedMat;
                            }
                        }

                        // Update CustomerInstalledMaterial untuk ONT pelanggan ke status 'dismantled'
                        \App\Models\CustomerInstalledMaterial::where('customer_id', $cbp->customer_id)
                            ->where(function ($q) use ($itemName, $material, $item) {
                                if (!empty($item['serial_number'])) {
                                    $q->where('serial_number', $item['serial_number']);
                                }
                                if ($material) {
                                    $q->orWhere('material_id', $material->id);
                                }
                                $q->orWhere('item_name', 'like', '%ont%')
                                  ->orWhere('item_name', 'like', '%modem%');
                            })
                            ->where('status', '!=', 'dismantled')
                            ->update([
                                'status' => 'dismantled',
                                'notes' => "Dicabut ({$qty} {$unit}) pada tiket CBP #{$cbp->cbp_number}",
                            ]);
                    }

                    $returnItems[] = [
                        'material_id' => $material?->id,
                        'material_name' => $itemName,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'condition' => $cond,
                        'notes' => 'Pencabutan ' . $itemName . ' dari pelanggan ' . ($customer?->name ?? '') . ' via CBP #' . $cbp->cbp_number,
                    ];

                    // Update CustomerInstalledMaterial untuk material lain jika cocok
                    if (!$isOntType) {
                        \App\Models\CustomerInstalledMaterial::where('customer_id', $cbp->customer_id)
                            ->where(function ($q) use ($itemName, $materialId) {
                                if ($materialId) $q->where('material_id', $materialId);
                                else $q->where('item_name', 'like', '%' . explode(' ', $itemName)[0] . '%');
                            })
                            ->where('status', '!=', 'dismantled')
                            ->update([
                                'status' => 'dismantled',
                                'notes' => "Dicabut ({$qty} {$unit}) pada tiket CBP #{$cbp->cbp_number}",
                            ]);
                    }
                }

                if (!empty($returnItems)) {
                    $technicianNames = $cbp->technicians()->pluck('name')->implode(', ') ?: auth()->user()->name;
                    $areaId = $cbp->area_id ?: ($customer?->area_id ?? null);

                    $handoverTrx = \App\Services\MaterialHandoverService::submitReturnHandover(
                        sourceType: 'cbp',
                        sourceId: $cbp->id,
                        sourceRef: $cbp->cbp_number,
                        items: $returnItems,
                        technicianName: $technicianNames,
                        areaId: $areaId,
                        notes: 'Pengembalian Cabut Perangkat ' . $cbp->cbp_number . ' (' . ($customer?->name ?? '') . ') - ' . count($returnItems) . ' item dicabut.',
                        customerId: $cbp->customer_id,
                        oldOntId: $uninstalledOnt?->id
                    );

                    // Catat ke customer_material_dismantles untuk riwayat historis
                    if ($handoverTrx) {
                        foreach ($returnItems as $rItem) {
                            \App\Models\CustomerMaterialDismantle::create([
                                'ticket_type' => 'cbp',
                                'ticket_id' => $cbp->id,
                                'ticket_number' => $cbp->cbp_number,
                                'customer_id' => $cbp->customer_id,
                                'material_id' => $rItem['material_id'] ?? null,
                                'item_name' => $rItem['material_name'] ?? 'Material Cabut',
                                'replacement_type' => 'full',
                                'quantity' => $rItem['quantity'],
                                'unit' => $rItem['unit'],
                                'condition' => (strtolower($rItem['condition']) === 'rusak' ? 'rusak_scrap' : 'normal_reusable'),
                                'damage_reason' => 'Pencabutan perangkat (Stop Berlangganan)',
                                'technician_id' => auth()->id(),
                                'technician_name' => $technicianNames,
                                'handover_status' => 'pending_handover',
                                'material_transaction_id' => $handoverTrx->id,
                            ]);
                        }
                    }
                }
            }
        }

        $oldStatus = $cbp->status;

        $cbp->update([
            'status' => 'completed',
            'notes' => $validated['notes'] ?? $cbp->notes,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'photo' => $primaryPhoto,
            'photos' => !empty($allPhotos) ? $allPhotos : null,
            'completed_at' => $validated['completed_at'] ?? $cbp->completed_at,
        ]);

        // Tutup otomatis jika ada tiket CBP lain milik pelanggan yang sama yang masih pending/assigned
        if ($cbp->customer_id) {
            CbpRequest::where('customer_id', $cbp->customer_id)
                ->where('id', '!=', $cbp->id)
                ->whereIn('status', ['pending', 'assigned'])
                ->update([
                    'status' => 'completed',
                    'completed_at' => $cbp->completed_at ?? now(),
                    'notes' => \Illuminate\Support\Facades\DB::raw("CONCAT(COALESCE(notes, ''), ' [Selesai bersamaan dengan penarikan tiket {$cbp->cbp_number}]')"),
                ]);
        }

        $itemSummary = !empty($returnItems) ? ' Material dicabut: ' . count($returnItems) . ' item.' : '';
        \App\Models\AuditLog::createLog(
            'Laporan CBP Selesai',
            $cbp,
            $oldStatus,
            'completed',
            "Menyelesaikan laporan cabut perangkat {$cbp->cbp_number}. Jam: {$validated['start_time']} - {$validated['end_time']}." . $itemSummary . $ontLog . (!empty($validated['notes']) ? " Catatan: {$validated['notes']}" : '')
        );

        return redirect()->back()->with('success', 'Laporan Pencabutan berhasil disimpan.');
    }

    public function dispatchToSchedule(Request $request, CbpRequest $cbp)
    {
        $user = $request->user();
        $canDispatch = $user && (
            $user->can('cbp_btn_dispatch') ||
            $user->can('cbp_btn_request') ||
            $user->can('cbp_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'dispatcher', 'cs', 'admin cs']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'dispatcher', 'cs', 'admin cs'])
        );
        if (!$canDispatch) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengirim ke Jadwal CBP.');
        }

        if ($cbp->status === 'canceled') {
            return redirect()->back()->with('error', 'Tiket CBP yang dibatalkan tidak dapat dikirim ke jadwal.');
        }

        if ($cbp->status === 'completed') {
            return redirect()->back()->with('error', 'Tiket CBP sudah selesai.');
        }

        $validated = $request->validate([
            'dispatch_notes' => 'nullable|string|max:500',
            'scheduled_date' => 'nullable|date',
        ]);

        $updateData = [
            'sent_to_schedule_at' => now(),
            'sent_to_schedule_by' => auth()->id(),
            'dispatch_notes' => $validated['dispatch_notes'] ?: $cbp->dispatch_notes,
        ];
        if (!empty($validated['scheduled_date'])) {
            $updateData['scheduled_date'] = $validated['scheduled_date'];
        } elseif (!$cbp->scheduled_date) {
            $updateData['scheduled_date'] = now()->toDateString();
        }

        $cbp->update($updateData);

        \App\Models\AuditLog::createLog(
            'Kirim ke Jadwal CBP',
            $cbp,
            'draft_request',
            'scheduled_ready',
            'Tiket CBP diteruskan ke Jadwal CBP untuk penugasan teknisi.' . (!empty($validated['dispatch_notes']) ? ' Catatan Disposisi: ' . $validated['dispatch_notes'] : '') . (!empty($updateData['scheduled_date']) ? ' Tgl Rencana: ' . $updateData['scheduled_date'] : '')
        );

        return redirect()->back()->with('success', 'Tiket ' . $cbp->cbp_number . ' berhasil dikirim ke Jadwal CBP!');
    }

    public function retractFromSchedule(Request $request, CbpRequest $cbp)
    {
        $user = $request->user();
        $canRetract = $user && (
            $user->can('cbp_btn_dispatch') ||
            $user->can('cbp_btn_request') ||
            $user->can('cbp_manage') ||
            $user->hasRole(['admin', 'super admin', 'superadmin', 'kepala teknisi', 'dispatcher', 'cs', 'admin cs']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'kepala teknisi', 'dispatcher', 'cs', 'admin cs'])
        );
        if (!$canRetract) {
            abort(403, 'Anda tidak memiliki hak akses untuk menarik kembali tiket CBP.');
        }

        if ($cbp->status === 'completed') {
            return redirect()->back()->with('error', 'Tiket sudah selesai dieksekusi teknisi dan tidak dapat ditarik kembali.');
        }

        $oldStatus = $cbp->status;

        // Jika statusnya 'assigned', lepaskan penugasan teknisi
        if ($cbp->status === 'assigned') {
            $cbp->technicians()->detach();
        }

        $cbp->update([
            'status' => 'pending',
            'sent_to_schedule_at' => null,
            'sent_to_schedule_by' => null,
            'assigned_to' => null,
        ]);

        \App\Models\AuditLog::createLog(
            'Tarik dari Jadwal CBP',
            $cbp,
            $oldStatus,
            'pending',
            'Tiket CBP ditarik kembali dari Jadwal CBP ke antrean request (jadwal & penugasan teknisi dibatalkan) oleh ' . ($user?->name ?? 'Admin')
        );

        return redirect()->back()->with('success', 'Tiket ' . $cbp->cbp_number . ' berhasil ditarik kembali dari Jadwal CBP ke Antrean Request Cabut.');
    }

    public function cancel(Request $request, CbpRequest $cbp)
    {
        $user = $request->user();
        $canCancel = $user && (
            $user->can('cbp_btn_cancel') ||
            $user->can('cbp_manage') ||
            $user->hasRole(['admin', 'super admin', 'superadmin', 'kepala teknisi', 'dispatcher', 'cs', 'admin cs']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'kepala teknisi', 'dispatcher', 'cs', 'admin cs'])
        );
        if (!$canCancel) {
            abort(403, 'Anda tidak memiliki hak akses untuk membatalkan CBP.');
        }

        if ($cbp->status === 'completed') {
            return redirect()->back()->with('error', 'Tiket CBP yang sudah selesai dieksekusi tidak dapat dibatalkan.');
        }

        $oldStatus = $cbp->status;
        $reason = trim((string) $request->input('cancel_reason', 'Pelanggan tidak jadi stop permanen / lanjut berlangganan'));
        if ($reason === '') {
            $reason = 'Pelanggan tidak jadi stop permanen / lanjut berlangganan';
        }
        $reactivateCustomer = $request->boolean('reactivate_customer', true);

        // Lepas teknisi dari penugasan jika tiket sudah assigned
        if ($cbp->status === 'assigned') {
            $cbp->technicians()->detach();
        }

        // Update status CBP
        $cbp->update([
            'status' => 'canceled',
            'assigned_to' => null,
            'notes' => ($cbp->notes ? $cbp->notes . ' | ' : '') . 'Dibatalkan: ' . $reason . ' (oleh ' . ($user?->name ?? 'Admin') . ')',
        ]);
        
        // Pulihkan / bersihkan transaksi material jika ada
        try {
            \App\Services\MaterialHandoverService::restoreDispatchedMaterials('cbp', $cbp->id, $cbp->area_id ?: ($cbp->customer?->area_id ?? null), $cbp->cbp_number);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[CbpRequestController::cancel] Gagal restore material: ' . $e->getMessage());
        }

        // Reaktivasi data pelanggan jika dipilih
        $customerMsg = '';
        if ($reactivateCustomer && $cbp->customer) {
            $customer = $cbp->customer;
            $oldCustomerStatus = $customer->status;

            // Periksa apakah pelanggan masih memiliki invoice yang belum lunas
            $hasUnpaidInvoice = \App\Models\Invoice::where('customer_id', $customer->id)
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->exists();

            $newStatus = $hasUnpaidInvoice ? 'suspended' : 'active';

            $customer->update([
                'status' => $newStatus,
                'service_status' => $newStatus,
                'notes' => ($customer->notes ? $customer->notes . "\n" : '') . "Reaktivasi: Pembatalan CBP #{$cbp->cbp_number} pada " . now()->format('d/m/Y H:i') . " ({$reason})",
            ]);

            \App\Models\AuditLog::createLog(
                'Reaktivasi Pelanggan (Batal CBP)',
                $customer,
                $oldCustomerStatus,
                $newStatus,
                "Status pelanggan dikembalikan menjadi '{$newStatus}' karena tiket CBP #{$cbp->cbp_number} dibatalkan (Alasan: {$reason}) oleh " . ($user?->name ?? 'Admin')
            );

            $customerMsg = " Data pelanggan ({$customer->name}) telah diaktifkan kembali ke status '{$newStatus}'.";
        }

        \App\Models\AuditLog::createLog(
            'CBP Dibatalkan',
            $cbp,
            $oldStatus,
            'canceled',
            "Membatalkan tiket cabut perangkat {$cbp->cbp_number}. Alasan: {$reason}" . ($reactivateCustomer ? " (Pelanggan di-reaktivasi)" : "")
        );

        return redirect()->back()->with('success', 'Permintaan Cabut Perangkat ' . $cbp->cbp_number . ' berhasil dibatalkan.' . $customerMsg);
    }

    public function destroy(CbpRequest $cbp)
    {
        $user = request()->user();
        $canDelete = $user && (
            $user->can('cbp_btn_delete') ||
            $user->can('cbp_manage') ||
            $user->hasRole(['admin', 'super admin', 'superadmin', 'kepala teknisi']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'kepala teknisi'])
        );
        if (!$canDelete) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus tiket CBP.');
        }

        $cbpNumber = $cbp->cbp_number;
        $customerName = $cbp->customer?->name ?? 'Pelanggan';
        $oldStatus = $cbp->status;

        // Pulihkan / bersihkan transaksi material jika ada
        try {
            \App\Services\MaterialHandoverService::restoreDispatchedMaterials('cbp', $cbp->id, $cbp->area_id ?: ($cbp->customer?->area_id ?? null), $cbp->cbp_number);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[CbpRequestController::destroy] Gagal restore material: ' . $e->getMessage());
        }

        // Hapus foto jika ada di disk storage
        if ($cbp->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($cbp->photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($cbp->photo);
        }

        // Putus relasi teknisi
        $cbp->technicians()->detach();

        // Bersihkan audit log terkait entitas ini
        \App\Models\AuditLog::where('entity_type', CbpRequest::class)
            ->where('entity_id', $cbp->id)
            ->delete();

        // Hapus record
        $cbp->delete();

        \App\Models\AuditLog::createLog(
            'Hapus Tiket CBP',
            null,
            $oldStatus,
            'deleted',
            "Menghapus tiket CBP {$cbpNumber} ({$customerName})"
        );

        return redirect()->back()->with('success', "Tiket CBP {$cbpNumber} berhasil dihapus.");
    }

    public function bulkDestroy(Request $request)
    {
        $user = $request->user();
        $canBulkDelete = $user && (
            $user->can('cbp_btn_delete_all') ||
            $user->can('cbp_btn_delete') ||
            $user->can('cbp_manage') ||
            $user->hasRole(['admin', 'super admin', 'superadmin', 'kepala teknisi']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'kepala teknisi'])
        );
        if (!$canBulkDelete) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus massal tiket CBP.');
        }

        $validated = $request->validate([
            'type' => 'required|in:selected,all',
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
        ]);

        $query = CbpRequest::query();

        // Terapkan scope agar non-admin tidak menghapus area di luar cakupannya
        DataScopeService::applyCbpScope($query);

        if ($validated['type'] === 'selected') {
            if (empty($validated['ids'])) {
                return redirect()->back()->with('error', 'Tidak ada data tiket CBP yang dipilih.');
            }
            $query->whereIn('id', $validated['ids']);
        }

        $count = $query->count();
        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada data tiket CBP yang dapat dihapus.');
        }

        $records = $query->get();
        foreach ($records as $record) {
            if ($record->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($record->photo);
            }
            $record->technicians()->detach();
            \App\Models\AuditLog::where('entity_type', CbpRequest::class)
                ->where('entity_id', $record->id)
                ->delete();
            $record->delete();
        }

        \App\Models\AuditLog::createLog(
            'Hapus Massal Tiket CBP',
            null,
            null,
            'deleted',
            "Menghapus {$count} tiket CBP secara massal (Tipe: {$validated['type']})"
        );

        return redirect()->back()->with('success', "Berhasil menghapus {$count} tiket CBP.");
    }

    /**
     * Resolusi Material ONT dari teks nama item / merk / model.
     */
    public static function resolveOntMaterialByName(?string $name): ?\App\Models\Material
    {
        if (empty($name)) return null;

        $nameLower = strtolower($name);
        $cleanName = preg_replace('/\(sn:.*?\)/i', '', $nameLower);
        $cleanName = preg_replace('/sn:.*$/i', '', $cleanName);
        $cleanName = trim(preg_replace('/^ont\s*[:\-\s]*/i', '', $cleanName));

        $materials = \App\Models\Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->where(function ($q) {
            $q->where('category', 'ONT')
              ->orWhere('category', 'like', '%ont%')
              ->orWhere('category', 'like', '%modem%');
        })->get();

        if ($materials->isEmpty()) {
            return null;
        }

        // 1. Direct contains check (e.g. "ONT ZTE F452")
        foreach ($materials as $m) {
            $matClean = trim(preg_replace('/^ont\s*[:\-\s]*/i', '', strtolower($m->name)));
            if (!empty($matClean) && (str_contains($cleanName, $matClean) || str_contains($matClean, $cleanName))) {
                return $m;
            }
        }

        // 2. Token match (zte, f452, f609, hg8245, etc.)
        $tokens = array_filter(preg_split('/[\s\-_/]+/', $cleanName), function ($t) {
            return strlen($t) >= 2 && !in_array($t, ['ont', 'modem', 'unit', 'gpon', 'xpon', 'pcs']);
        });

        $bestMat = null;
        $maxScore = 0;

        foreach ($materials as $m) {
            $mLower = strtolower($m->name);
            $score = 0;
            foreach ($tokens as $token) {
                if (str_contains($mLower, $token)) {
                    $score += (strlen($token) > 3 ? 3 : 1);
                }
            }
            if ($score > $maxScore) {
                $maxScore = $score;
                $bestMat = $m;
            }
        }

        if ($bestMat && $maxScore > 0) {
            return $bestMat;
        }

        // 3. Known brand fallback
        $knownBrands = ['zte', 'huawei', 'fiberhome', 'netvibes', 'c-link', 'clink', 'vsol', 'hioso', 'totolink', 'ml212'];
        foreach ($knownBrands as $brand) {
            if (str_contains($nameLower, $brand)) {
                $match = $materials->first(fn($m) => str_contains(strtolower($m->name), $brand));
                if ($match) return $match;
            }
        }

        return null;
    }
}
