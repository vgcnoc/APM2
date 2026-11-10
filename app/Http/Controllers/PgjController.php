<?php

namespace App\Http\Controllers;

use App\Services\RestockStockService;

use App\Models\PgjTicket;
use App\Models\PgjMaterial;
use App\Models\PgjMaterialRequest;
use App\Models\Area;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Customer;
use App\Models\User;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\AuditLog;
use App\Services\DataScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class PgjController extends Controller
{
    public function __construct()
    {
        try {
            Role::firstOrCreate(['name' => 'kepala teknisi']);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Halaman Utama Penanganan Gangguan Jalur (PGJ) - Penerimaan & Penanganan Admin
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgj')) {
            if ($user->can('menu_pgj_laporan')) {
                return redirect()->route('pgj.laporan');
            }
            if ($user->can('menu_pgj_jadwal')) {
                return redirect()->route('pgj.jadwal');
            }
            abort(403, 'Akses ke modul PGJ tidak diizinkan.');
        }

        $query = PgjTicket::with([
            'area',
            'olt',
            'odc',
            'odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'materialRequests.material',
            'materialRequests.requester',
            'materialRequests.reviewer',
            'resolvedByUser'
        ]);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgjScope($query);

        // Filter pencarian
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhere('core_details', 'like', "%{$search}%")
                  ->orWhere('backbone_source', 'like', "%{$search}%")
                  ->orWhere('provider_name', 'like', "%{$search}%")
                  ->orWhere('circuit_id', 'like', "%{$search}%")
                  ->orWhereHas('olt', function($lq) use ($search) {
                      $lq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odc', function($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odp', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter prioritas
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter segmen
        if ($request->filled('segment_type')) {
            $query->where('segment_type', $request->segment_type);
        }

        // Filter area
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        // Filter periode tanggal
        if ($request->filled('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', now()->subMonth()->month)
                          ->whereYear('created_at', now()->subMonth()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date')) {
                        $query->whereDate('created_at', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $query->whereDate('created_at', '<=', $request->end_date);
                    }
                    break;
            }
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Hitung statistik kasus jalur dengan menghormati data scope user
        $baseCountQuery = PgjTicket::query();
        DataScopeService::applyPgjScope($baseCountQuery);

        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'admin_handling' => (clone $baseCountQuery)->where('status', 'admin_handling')->count(),
            'escalated_to_lead' => (clone $baseCountQuery)->where('status', 'escalated_to_lead')->count(),
            'scheduled' => (clone $baseCountQuery)->where('status', 'scheduled')->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
        ];

        $areas = DataScopeService::getScopedAreas($user, 'pgj', ['id', 'name'], function($q) {
            $q->withCount(['customers' => function($cq) {
                $cq->whereIn('service_status', ['active', 'isolir']);
            }]);
        });

        $oltQuery = Olt::select('id', 'name', 'area_id', 'ip_address', 'location', 'total_pon_ports')->withCount('odcs')->orderBy('name');
        DataScopeService::applyOltScope($oltQuery, $user);
        $olts = $oltQuery->get();

        $odcQuery = Odc::withCount('odps')->orderBy('name');
        DataScopeService::applyOdcScope($odcQuery, $user);
        $odcs = $odcQuery->get();

        $odpQuery = Odp::with('odc')->orderBy('name');
        DataScopeService::applyOdpScope($odpQuery, $user);
        $odps = $odpQuery->get();

        $oltCustomerCounts = [];
        foreach ($olts as $o) {
            $oltCustomerCounts[$o->id] = Customer::whereHas('ont.odp.odc', function($q) use ($o) {
                $q->where('olt_id', $o->id);
            })->whereIn('service_status', ['active', 'isolir'])->count();
        }

        $totalActiveCustQuery = Customer::whereIn('service_status', ['active', 'isolir']);
        DataScopeService::applyCustomerScope($totalActiveCustQuery, $user);
        $totalActiveCustomers = $totalActiveCustQuery->count();

        $technicians = User::forAssignment()->where(function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
                  ->orWhere('name', 'like', '%teknisi%')
                  ->orWhere('name', 'like', '%serbu%');
            })
            ->orWhereIn('role', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
            ->orWhere('role', 'like', '%teknisi%')
            ->orWhere('role', 'like', '%serbu%');
        })->orderBy('name')->get();

        $leadTechnicians = User::forAssignment()->role(['kepala teknisi', 'admin', 'noc', 'manager'])->select('id', 'name')->get();
        if ($leadTechnicians->isEmpty()) {
            $leadTechnicians = User::forAssignment()->select('id', 'name')->get();
        }

        return Inertia::render('Pgj/Index', [
            'tickets' => $tickets,
            'areas' => $areas,
            'olts' => $olts,
            'oltCustomerCounts' => $oltCustomerCounts,
            'totalActiveCustomers' => $totalActiveCustomers,
            'odcs' => $odcs,
            'odps' => $odps,
            'technicians' => $technicians,
            'leadTechnicians' => $leadTechnicians,
            'counts' => $counts,
            'filters' => $request->only(['search', 'status', 'priority', 'segment_type', 'area_id', 'period', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Halaman Penjadwalan & Penyiapan Material Jalur (Kepala Teknisi)
     */
    public function jadwal(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgj_jadwal')) {
            if ($user->can('menu_pgj_laporan')) {
                return redirect()->route('pgj.laporan');
            }
            if ($user->can('menu_pgj')) {
                return redirect()->route('pgj.index');
            }
            abort(403, 'Akses ke jadwal PGJ tidak diizinkan.');
        }

        $status = $request->get('status', 'active');

        $baseCountQuery = PgjTicket::query();
        DataScopeService::applyPgjScope($baseCountQuery);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $baseCountQuery->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhereHas('odc', function($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odp', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $baseCountQuery->where('area_id', $request->area_id);
        }

        $counts = [
            'active' => (clone $baseCountQuery)->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress'])->count(),
            'waiting' => (clone $baseCountQuery)->where('status', 'escalated_to_lead')->count(),
            'scheduled' => (clone $baseCountQuery)->whereIn('status', ['scheduled', 'in_progress'])->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
            'all' => (clone $baseCountQuery)->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress', 'completed'])->count(),
        ];

        $query = PgjTicket::with([
            'area',
            'olt',
            'odc',
            'odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'materialRequests.material',
            'materialRequests.requester',
            'materialRequests.reviewer',
        ]);

        if ($status === 'waiting') {
            $query->where('status', 'escalated_to_lead');
        } elseif ($status === 'scheduled') {
            $query->whereIn('status', ['scheduled', 'in_progress']);
        } elseif ($status === 'completed') {
            $query->where('status', 'completed');
        } elseif ($status === 'all') {
            $query->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress', 'completed']);
        } else {
            $status = 'active';
            $query->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress']);
        }

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgjScope($query);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhere('backbone_source', 'like', "%{$search}%")
                  ->orWhere('provider_name', 'like', "%{$search}%")
                  ->orWhere('circuit_id', 'like', "%{$search}%")
                  ->orWhereHas('olt', function($lq) use ($search) {
                      $lq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odc', function($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odp', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('segment_type')) {
            $query->where('segment_type', $request->segment_type);
        }

        if ($request->filled('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date')) {
                        $query->whereDate('created_at', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $query->whereDate('created_at', '<=', $request->end_date);
                    }
                    break;
            }
        }

        $tickets = $query->orderBy('priority', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate(15)
                         ->withQueryString();

        $areas = DataScopeService::getScopedAreas($user, 'pgj');

        $technicians = User::forAssignment()->where(function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
                  ->orWhere('name', 'like', '%teknisi%')
                  ->orWhere('name', 'like', '%serbu%');
            })
            ->orWhereIn('role', ['teknisi', 'kepala teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance', 'teknisi-2', 'maintenance-1'])
            ->orWhere('role', 'like', '%teknisi%')
            ->orWhere('role', 'like', '%serbu%');
        })->orderBy('name')->get();

        $materials = Material::with('stocks')
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get();
        $materialTransactions = fn () => RestockStockService::getRestockTransactions();

        return Inertia::render('Pgj/Jadwal', [
            'tickets' => $tickets,
            'areas' => $areas,
            'technicians' => $technicians,
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'counts' => $counts,
            'filters' => array_merge(
                $request->only(['search', 'priority', 'segment_type', 'area_id', 'period', 'start_date', 'end_date']),
                ['status' => $status]
            ),
        ]);
    }

    /**
     * Halaman Pelaporan Hasil Penanganan Jalur (Teknisi)
     */
    public function laporan(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgj_laporan')) {
            if ($user->can('menu_pgj')) {
                return redirect()->route('pgj.index');
            }
            if ($user->can('menu_pgj_jadwal')) {
                return redirect()->route('pgj.jadwal');
            }
            abort(403, 'Akses ke laporan PGJ tidak diizinkan.');
        }

        $status = $request->get('status', 'all');

        $baseCountQuery = PgjTicket::query()->whereIn('status', ['scheduled', 'completed']);
        DataScopeService::applyPgjScope($baseCountQuery);

        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'scheduled' => (clone $baseCountQuery)->where('status', 'scheduled')->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
            'backbone' => (clone $baseCountQuery)->where('segment_type', 'backbone')->count(),
        ];

        $query = PgjTicket::with([
            'area',
            'olt',
            'odc',
            'odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'materialRequests.material',
            'materialRequests.requester',
            'materialRequests.reviewer',
            'resolvedByUser'
        ]);

        if ($status === 'scheduled' || $status === 'waiting_report') {
            $query->where('status', 'scheduled');
        } elseif ($status === 'completed') {
            $query->where('status', 'completed');
        } else {
            $query->whereIn('status', ['scheduled', 'completed']);
        }

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgjScope($query);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhere('action_taken', 'like', "%{$search}%")
                  ->orWhere('core_details', 'like', "%{$search}%")
                  ->orWhere('backbone_source', 'like', "%{$search}%")
                  ->orWhere('provider_name', 'like', "%{$search}%")
                  ->orWhere('circuit_id', 'like', "%{$search}%")
                  ->orWhereHas('olt', function($lq) use ($search) {
                      $lq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odc', function($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odp', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('segment_type')) {
            $query->where('segment_type', $request->segment_type);
        }

        if ($request->filled('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date')) {
                        $query->whereDate('created_at', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $query->whereDate('created_at', '<=', $request->end_date);
                    }
                    break;
            }
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $areas = DataScopeService::getScopedAreas($user, 'pgj');

        return Inertia::render('Pgj/Laporan', [
            'tickets' => $tickets,
            'areas' => $areas,
            'counts' => $counts,
            'materials' => \App\Models\Material::where('is_active', true)->orWhereNull('is_active')->orderBy('name')->get(['id', 'name', 'unit', 'category']),
            'filters' => array_merge(
                $request->only(['search', 'priority', 'segment_type', 'area_id', 'period', 'start_date', 'end_date']),
                ['status' => $status]
            ),
        ]);
    }

    /**
     * Hitung Estimasi Dampak Pelanggan (Helper)
     */
    private function calculateImpact(string $segmentType, ?int $odcId, ?int $odpId, ?int $areaId, ?int $oltId = null): int
    {
        if ($segmentType === 'odp' && $odpId) {
            // Hitung pelanggan yang ont-nya di odp ini
            return Customer::whereHas('ont', function($q) use ($odpId) {
                $q->where('odp_id', $odpId);
            })->whereIn('service_status', ['active', 'isolir'])->count();
        }

        if (($segmentType === 'odc' || $segmentType === 'distribution') && $odcId) {
            // Hitung pelanggan di seluruh odp di bawah odc ini
            return Customer::whereHas('ont.odp', function($q) use ($odcId) {
                $q->where('odc_id', $odcId);
            })->whereIn('service_status', ['active', 'isolir'])->count();
        }

        if ($segmentType === 'backbone') {
            // 1. Jika OLT spesifik dipilih
            if ($oltId) {
                $cnt = Customer::whereHas('ont.odp.odc', function($q) use ($oltId) {
                    $q->where('olt_id', $oltId);
                })->whereIn('service_status', ['active', 'isolir'])->count();
                if ($cnt > 0) return $cnt;
            }

            // 2. Jika ODC dipilih
            if ($odcId) {
                return Customer::whereHas('ont.odp', function($q) use ($odcId) {
                    $q->where('odc_id', $odcId);
                })->whereIn('service_status', ['active', 'isolir'])->count();
            }

            // 3. Jika Area dipilih
            if ($areaId) {
                return Customer::where('area_id', $areaId)
                    ->whereIn('service_status', ['active', 'isolir'])
                    ->count();
            }

            // 4. Jika backbone global / sumber internet utama
            return Customer::whereIn('service_status', ['active', 'isolir'])->count();
        }

        return 0;
    }

    /**
     * Simpan Temuan / Komplain Gangguan Jalur Baru (Admin)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $canCreate = $user && (
            $user->can('pgj_btn_create') ||
            $user->can('pgj_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'cs', 'admin cs', 'noc']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'cs', 'admin cs', 'noc'])
        );
        if (!$canCreate) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat laporan PGJ.');
        }

        $validated = $request->validate([
            'segment_type' => 'required|in:backbone,odc,odp,distribution,other',
            'backbone_source' => 'nullable|string|max:255',
            'provider_name' => 'nullable|string|max:255',
            'circuit_id' => 'nullable|string|max:100',
            'area_id' => 'nullable|exists:areas,id',
            'olt_id' => 'nullable|exists:olts,id',
            'odc_id' => 'nullable|exists:odcs,id',
            'odp_id' => 'nullable|exists:odps,id',
            'location_name' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'incident_coordinates' => 'nullable|array',
            'incident_coordinates.*.name' => 'nullable|string|max:255',
            'incident_coordinates.*.latitude' => 'nullable|numeric',
            'incident_coordinates.*.longitude' => 'nullable|numeric',
            'incident_coordinates.*.notes' => 'nullable|string',
            'otdr_distance' => 'nullable|string|max:100',
            'source_report' => 'required|string|max:100',
            'category' => 'required|string|max:150',
            'priority' => 'required|in:low,medium,high,critical',
            'reported_issue' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validated['segment_type'] === 'backbone') {
            $validated['odp_id'] = null; // Backbone tidak relevan dengan ODP
        }

        // Filter dan rapikan incident_coordinates
        $filteredCoords = [];
        if (!empty($validated['incident_coordinates']) && is_array($validated['incident_coordinates'])) {
            foreach ($validated['incident_coordinates'] as $coord) {
                if (!empty($coord['latitude']) || !empty($coord['longitude']) || !empty($coord['name'])) {
                    $filteredCoords[] = [
                        'name' => $coord['name'] ?? 'Titik Insiden Lapangan',
                        'latitude' => isset($coord['latitude']) && $coord['latitude'] !== '' ? (float) $coord['latitude'] : null,
                        'longitude' => isset($coord['longitude']) && $coord['longitude'] !== '' ? (float) $coord['longitude'] : null,
                        'notes' => $coord['notes'] ?? null,
                    ];
                }
            }
        }
        $incidentCoordsToSave = !empty($filteredCoords) ? $filteredCoords : null;
        if (empty($validated['latitude']) && !empty($filteredCoords[0]['latitude'])) {
            $validated['latitude'] = $filteredCoords[0]['latitude'];
            $validated['longitude'] = $filteredCoords[0]['longitude'];
        }

        // Otomatis hitung perkiraan pelanggan terdampak
        $impacted = $this->calculateImpact(
            $validated['segment_type'],
            $validated['odc_id'] ?? null,
            $validated['odp_id'] ?? null,
            $validated['area_id'] ?? null,
            $validated['olt_id'] ?? null
        );

        // Jika lokasi/koordinat kosong, ambil dari OLT, ODC atau ODP terpilih
        if (empty($validated['location_name'])) {
            if (!empty($validated['olt_id'])) {
                $olt = Olt::find($validated['olt_id']);
                $validated['location_name'] = $olt?->location ?: 'POP / OLT ' . $olt?->name;
                $validated['latitude'] = $validated['latitude'] ?? $olt?->latitude;
                $validated['longitude'] = $validated['longitude'] ?? $olt?->longitude;
            } elseif (!empty($validated['odp_id'])) {
                $odp = Odp::find($validated['odp_id']);
                $validated['location_name'] = $odp?->address ?: 'Lokasi ' . $odp?->name;
                $validated['latitude'] = $validated['latitude'] ?? $odp?->latitude;
                $validated['longitude'] = $validated['longitude'] ?? $odp?->longitude;
            } elseif (!empty($validated['odc_id'])) {
                $odc = Odc::find($validated['odc_id']);
                $validated['location_name'] = $odc?->location ?: 'Lokasi ' . $odc?->name;
                $validated['latitude'] = $validated['latitude'] ?? $odc?->latitude;
                $validated['longitude'] = $validated['longitude'] ?? $odc?->longitude;
            }
        }

        $ticket = PgjTicket::create([
            'segment_type' => $validated['segment_type'],
            'backbone_source' => $validated['backbone_source'] ?? null,
            'provider_name' => $validated['provider_name'] ?? null,
            'circuit_id' => $validated['circuit_id'] ?? null,
            'area_id' => $validated['area_id'] ?? null,
            'olt_id' => $validated['olt_id'] ?? null,
            'odc_id' => $validated['odc_id'] ?? null,
            'odp_id' => $validated['odp_id'] ?? null,
            'affected_customers_count' => $impacted,
            'location_name' => $validated['location_name'] ?? 'Titik Jalur Jaringan',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'incident_coordinates' => $incidentCoordsToSave,
            'otdr_distance' => $validated['otdr_distance'] ?? null,
            'source_report' => $validated['source_report'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'admin_handling',
            'reported_issue' => $validated['reported_issue'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        AuditLog::createLog(
            'Buat Tiket PGJ Baru',
            $ticket,
            null,
            'admin_handling',
            "Menerima temuan gangguan jalur {$ticket->ticket_number} [{$ticket->segment_type}]: {$ticket->category} (Dampak: {$impacted} pelanggan)"
        );

        return redirect()->back()->with('success', "Tiket Penanganan Gangguan Jalur #{$ticket->ticket_number} berhasil dibuat. Estimasi dampak: {$impacted} pelanggan.");
    }

    /**
     * Selesaikan Langsung oleh Admin (Jarak Jauh / False Alarm / Pulih Sendiri)
     */
    public function resolveByAdmin(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        $canResolve = $user && (
            $user->can('pgj_btn_resolve') ||
            $user->can('pgj_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'cs', 'admin cs', 'noc']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'cs', 'admin cs', 'noc'])
        );
        if (!$canResolve) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyelesaikan PGJ.');
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string',
        ]);

        $oldStatus = $pgj->status;
        $pgj->update([
            'status' => 'completed',
            'admin_notes' => $validated['admin_notes'],
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'PGJ Selesai oleh Admin',
            $pgj,
            $oldStatus,
            'completed',
            'Admin menyelesaikan gangguan jalur dari jarak jauh: ' . $validated['admin_notes']
        );

        return redirect()->back()->with('success', "Tiket PGJ #{$pgj->ticket_number} berhasil diselesaikan oleh Admin.");
    }

    /**
     * Eskalasi ke Kepala Teknisi untuk Penanganan Lapangan
     */
    public function escalateToLead(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        $canEscalate = $user && (
            $user->can('pgj_btn_escalate') ||
            $user->can('pgj_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'cs', 'admin cs', 'noc']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'cs', 'admin cs', 'noc'])
        );
        if (!$canEscalate) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengeskalasi PGJ.');
        }

        $validated = $request->validate([
            'escalation_reason' => 'required|string',
            'lead_technician_id' => 'nullable|exists:users,id',
            'admin_notes' => 'nullable|string',
        ]);

        $oldStatus = $pgj->status;
        $pgj->update([
            'status' => 'escalated_to_lead',
            'escalation_reason' => $validated['escalation_reason'],
            'admin_notes' => $validated['admin_notes'] ?: $pgj->admin_notes,
            'lead_technician_id' => $validated['lead_technician_id'] ?? $pgj->lead_technician_id,
        ]);

        AuditLog::createLog(
            'Eskalasi PGJ ke Ka. Teknisi',
            $pgj,
            $oldStatus,
            'escalated_to_lead',
            'Admin mengeskalasi perbaikan jalur ke Kepala Teknisi: ' . $validated['escalation_reason']
        );

        return redirect()->back()->with('success', "Tiket PGJ #{$pgj->ticket_number} telah dieskalasi ke Kepala Teknisi untuk dijadwalkan.");
    }

    /**
     * Penjadwalan & Penyiapan Material oleh Kepala Teknisi
     */
    public function schedule(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        $canSchedule = $user && (
            $user->can('pgj_btn_assign') ||
            $user->can('pgj_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'dispatcher', 'maintenance']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'dispatcher', 'maintenance'])
        );
        if (!$canSchedule) {
            abort(403, 'Anda tidak memiliki hak akses untuk menjadwalkan teknisi PGJ.');
        }

        // Normalisasi parameter teknisi
        if ($request->has('technicians') && !$request->has('technician_ids')) {
            $request->merge(['technician_ids' => $request->technicians]);
        }
        if ($request->has('technician_ids') && !$request->has('technicians')) {
            $request->merge(['technicians' => $request->technician_ids]);
        }

        // Normalisasi parameter materials
        if ($request->has('materials') && is_array($request->materials)) {
            $mats = $request->materials;
            foreach ($mats as $k => $m) {
                if (isset($m['quantity_prepared']) && !isset($m['quantity'])) {
                    $mats[$k]['quantity'] = $m['quantity_prepared'];
                } elseif (isset($m['quantity']) && !isset($m['quantity_prepared'])) {
                    $mats[$k]['quantity_prepared'] = $m['quantity'];
                }
            }
            $request->merge(['materials' => $mats]);
        }

        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:100',
            'lead_notes' => 'nullable|string',
            'technician_ids' => 'required_without:technicians|array|min:1',
            'technician_ids.*' => 'exists:users,id',
            'technicians' => 'sometimes|array',
            'technicians.*' => 'exists:users,id',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.material_transaction_item_id' => 'nullable',
            'materials.*.trx_number' => 'nullable|string|max:100',
            'materials.*.quantity' => 'nullable|numeric|min:0.01',
            'materials.*.quantity_prepared' => 'nullable|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string',
            'materials.*.notes' => 'nullable|string',
        ]);

        $oldStatus = $pgj->status;

        $pgj->update([
            'status' => 'scheduled',
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'lead_notes' => $validated['lead_notes'] ?? null,
            'lead_technician_id' => auth()->id(),
        ]);

        // Simpan penugasan tim teknisi
        $techIds = $request->technician_ids ?? $request->technicians ?? [];
        $pgj->technicians()->sync($techIds);

        // Kelola alokasi material gudang jalur (pulihkan alokasi jadwal sebelumnya terlebih dahulu secara simetris, jangan hapus tambahan yang sudah disetujui)
        $restoredScheduleMats = [];
        $prevScheduleOuts = MaterialTransaction::with('items.material')
            ->where('source_type', 'pgj')
            ->where('source_id', $pgj->id)
            ->where('type', 'out')
            ->where('transaction_number', 'like', 'OUT-PGJ-%')
            ->where('transaction_number', 'not like', 'OUT-PGJ-REQ-%')
            ->where('transaction_number', 'not like', 'OUT-PGJ-EXTRA-%')
            ->where('transaction_number', 'not like', 'OUT-PGJ-OVERAGE-%')
            ->get();

        foreach ($prevScheduleOuts as $prevOut) {
            $restoreAreaId = $prevOut->area_id ?: $pgj->area_id;
            if ($prevOut->status === 'approved' && !$prevOut->is_migration) {
                foreach ($prevOut->items as $prevItem) {
                    if ($prevItem->material && (float) $prevItem->quantity > 0) {
                        $res = \App\Services\MaterialService::restoreOperationalStock(
                            $prevItem->material,
                            (float) $prevItem->quantity,
                            $restoreAreaId,
                            $prevItem->unit
                        );
                        $restoredScheduleMats[$prevItem->material->id] = ($restoredScheduleMats[$prevItem->material->id] ?? 0) + $res['base_qty'];
                    }
                }
            }
            $prevOut->skipStockRestoreOnDelete = true;
            $prevOut->items()->delete();
            $prevOut->delete();
        }

        $previousMaterials = $pgj->materials()
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', 'not like', '%Tambahan disetujui%');
            })
            ->get();
        foreach ($previousMaterials as $prevMat) {
            $prevQty = (float) $prevMat->quantity_prepared;
            if ($prevQty > 0 && $prevMat->material_id && empty($restoredScheduleMats[(int) $prevMat->material_id])) {
                $matModel = Material::find($prevMat->material_id);
                if ($matModel) {
                    \App\Services\MaterialService::restoreOperationalStock($matModel, $prevQty, $pgj->area_id, $prevMat->unit);
                }
            }
        }

        $pgj->materials()
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', 'not like', '%Tambahan disetujui%');
            })
            ->delete();

        if (!empty($validated['materials'])) {
            $techNames = User::whereIn('id', $techIds)->pluck('name')->join(', ');

            $sourceOrders = array_filter(array_unique(array_column($validated['materials'], 'trx_number')));
            $orderRefStr = !empty($sourceOrders) ? ' (Ref Order/SJ: ' . implode(', ', $sourceOrders) . ')' : '';

            $trxNumber = \App\Services\MaterialService::generateTransactionNumber('OUT-PGJ');
            $transaction = MaterialTransaction::create([
                'transaction_number' => $trxNumber,
                'user_id' => auth()->id(),
                'type' => 'out',
                'status' => 'approved',
                'request_type' => 'regular',
                'technician_name' => $techNames ?: auth()->user()->name,
                'purpose' => "Material Jalur PGJ #{$pgj->ticket_number} ({$pgj->segment_type}: {$pgj->category}){$orderRefStr}",
                'source_type' => 'pgj',
                'source_id' => $pgj->id,
                'source_reference' => $pgj->ticket_number,
                'date' => now(),
                'area_id' => $pgj->area_id,
            ]);

            $totalScheduleCost = 0;
            foreach ($validated['materials'] as $matItem) {
                if (empty($matItem['material_id'])) continue;
                $material = Material::find($matItem['material_id']);
                $qty = (float) ($matItem['quantity'] ?? $matItem['quantity_prepared'] ?? 0);
                if ($qty <= 0) continue;

                if ($material) {
                    $unit = $matItem['unit'] ?? $material->unit;
                    $stockRes = \App\Services\MaterialService::deductOperationalStock($material, $qty, $pgj->area_id, $unit);

                    $ppu = \App\Services\MaterialService::calculateUnitPrice($material, $unit);
                    $itemTotal = $ppu * $qty;
                    $totalScheduleCost += $itemTotal;

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $transaction->id,
                        'material_id' => $material->id,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'price_per_unit' => $ppu,
                        'total_price' => $itemTotal,
                        'stock_before' => $stockRes['stock_before'],
                        'stock_after' => $stockRes['stock_after'],
                    ]);

                    PgjMaterial::create([
                        'pgj_ticket_id' => $pgj->id,
                        'material_id' => $material->id,
                        'material_transaction_item_id' => $matItem['material_transaction_item_id'] ?? null,
                        'trx_number' => $matItem['trx_number'] ?? null,
                        'quantity_prepared' => $qty,
                        'quantity_used' => $qty,
                        'quantity_returned' => 0,
                        'unit' => $unit,
                        'notes' => $matItem['notes'] ?? null,
                    ]);
                }
            }
            $transaction->update(['total_cost' => $totalScheduleCost]);
        }

        AuditLog::createLog(
            'Penjadwalan PGJ oleh Ka. Teknisi',
            $pgj,
            $oldStatus,
            'scheduled',
            "Ka. Teknisi menjadwalkan perbaikan jalur pada {$validated['scheduled_date']} dengan " . count($validated['technician_ids']) . " teknisi."
        );

        return redirect()->back()->with('success', "Tiket PGJ #{$pgj->ticket_number} berhasil dijadwalkan dan material telah disiapkan dari gudang.");
    }

    /**
     * Pelaporan Hasil Restorasi Jalur oleh Teknisi
     */
    public function submitReport(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        $canReport = $user && (
            $user->can('pgj_btn_report') ||
            $user->can('pgj_manage') ||
            $user->hasAnyRole(['admin', 'kepala teknisi', 'teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance']) ||
            str_contains(strtolower($user->role ?? ''), 'teknisi') ||
            str_contains(strtolower($user->role ?? ''), 'serbu')
        );
        if (!$canReport) {
            abort(403, 'Anda tidak memiliki hak akses untuk melaporkan hasil restorasi PGJ.');
        }

        // Jika laporan sudah selesai, hanya Admin / Kepala Teknisi (pgj_manage) yang boleh merevisi
        if ($pgj->status === 'completed') {
            $canRevise = $user && (
                $user->can('pgj_manage') ||
                $user->hasAnyRole(['admin', 'kepala teknisi', 'noc', 'admin cs']) ||
                in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'noc', 'admin cs'])
            );
            if (!$canRevise) {
                return redirect()->back()->with('error', 'Laporan restorasi jalur ini sudah selesai dikerjakan dan terkunci. Hubungi Kepala Teknisi atau Admin jika diperlukan revisi data.');
            }
        }

        // Jika scope 'own' (teknisi biasa), verifikasi bahwa user memang ditugaskan pada PGJ ini
        if ($user && \App\Services\DataScopeService::getScopeLevel($user, 'pgj') === 'own') {
            $isAssigned = $pgj->technicians()->where('users.id', $user->id)->exists()
                || $pgj->created_by === $user->id
                || $pgj->lead_technician_id === $user->id;
            if (!$isAssigned) {
                abort(403, 'Anda hanya dapat melaporkan tugas PGJ yang ditugaskan kepada Anda.');
            }
        }
        $validated = $request->validate([
            'action_taken' => 'required|string',
            'core_details' => 'nullable|string|max:255',
            'optical_loss_before' => 'nullable|string|max:100',
            'optical_loss_after' => 'nullable|string|max:100',
            'start_time' => 'nullable|string|max:50',
            'end_time' => 'nullable|string|max:50',
            'resolution_notes' => 'nullable|string',
            'photo_before' => 'nullable|image|max:10240',
            'photo_after' => 'nullable|image|max:10240',
            'handling_photos_files.*' => 'nullable|image|max:10240',
            'handling_photos_captions' => 'nullable|array',
            'incident_coordinates' => 'nullable|array',
            'incident_coordinates.*.name' => 'nullable|string|max:255',
            'incident_coordinates.*.latitude' => 'nullable|numeric',
            'incident_coordinates.*.longitude' => 'nullable|numeric',
            'incident_coordinates.*.notes' => 'nullable|string',
            'materials' => 'nullable|array',
            'materials.*.pgj_material_id' => 'required|exists:pgj_materials,id',
            'materials.*.quantity_used' => 'required|numeric|min:0',
        ]);

        // 1. Tangani incident_coordinates (bisa lebih dari 1)
        $incidentCoords = [];
        if ($request->has('incident_coordinates') && is_array($request->incident_coordinates)) {
            foreach ($request->incident_coordinates as $coord) {
                if (!empty($coord['latitude']) || !empty($coord['longitude']) || !empty($coord['name'])) {
                    $incidentCoords[] = [
                        'name' => $coord['name'] ?? 'Titik Insiden Lapangan',
                        'latitude' => isset($coord['latitude']) && $coord['latitude'] !== '' ? (float) $coord['latitude'] : null,
                        'longitude' => isset($coord['longitude']) && $coord['longitude'] !== '' ? (float) $coord['longitude'] : null,
                        'notes' => $coord['notes'] ?? null,
                    ];
                }
            }
        }
        $incidentCoordsToSave = !empty($incidentCoords) ? $incidentCoords : ($pgj->incident_coordinates ?: null);
        $primaryLat = $pgj->latitude;
        $primaryLng = $pgj->longitude;
        if (!empty($incidentCoords[0]['latitude'])) {
            $primaryLat = $incidentCoords[0]['latitude'];
            $primaryLng = $incidentCoords[0]['longitude'];
        }

        // 2. Tangani foto penanganan (handling_photos)
        $handlingPhotos = [];
        if ($request->has('existing_handling_photos')) {
            $existing = is_string($request->existing_handling_photos)
                ? json_decode($request->existing_handling_photos, true)
                : $request->existing_handling_photos;
            if (is_array($existing)) {
                $handlingPhotos = $existing;
            }
        } elseif (is_array($pgj->handling_photos)) {
            $handlingPhotos = $pgj->handling_photos;
        }

        // Simpan file-file foto penanganan baru
        if ($request->hasFile('handling_photos_files')) {
            $files = $request->file('handling_photos_files');
            $captions = $request->input('handling_photos_captions', []);
            if (is_array($files)) {
                foreach ($files as $idx => $file) {
                    if ($file && $file->isValid()) {
                        $storedPath = $file->store('pgj_photos', 'public');
                        $caption = isset($captions[$idx]) && trim($captions[$idx]) !== '' ? trim($captions[$idx]) : 'Dokumentasi Penanganan Lapangan';
                        $handlingPhotos[] = [
                            'path' => $storedPath,
                            'caption' => $caption,
                            'uploaded_at' => now()->format('Y-m-d H:i:s'),
                        ];
                    }
                }
            }
        }

        $photoBeforePath = $pgj->photo_before;
        if ($request->hasFile('photo_before')) {
            $photoBeforePath = $request->file('photo_before')->store('pgj_photos', 'public');
            $handlingPhotos[] = [
                'path' => $photoBeforePath,
                'caption' => 'Foto Kerusakan (Sebelum Restorasi)',
                'uploaded_at' => now()->format('Y-m-d H:i:s'),
            ];
        }

        $photoAfterPath = $pgj->photo_after;
        if ($request->hasFile('photo_after')) {
            $photoAfterPath = $request->file('photo_after')->store('pgj_photos', 'public');
            $handlingPhotos[] = [
                'path' => $photoAfterPath,
                'caption' => 'Foto Hasil Restorasi / Splicing (Sesudah)',
                'uploaded_at' => now()->format('Y-m-d H:i:s'),
            ];
        }

        if (empty($photoBeforePath) && !empty($handlingPhotos[0]['path'])) {
            $photoBeforePath = $handlingPhotos[0]['path'];
        }
        if (empty($photoAfterPath) && count($handlingPhotos) > 1) {
            $photoAfterPath = end($handlingPhotos)['path'];
        }

        // Rekonsiliasi material terpakai vs sisa dikembalikan ke gudang (Serah Terima Gudang)
        if (!empty($validated['materials'])) {
            \App\Services\MaterialHandoverService::replacePendingOrApprovedReturnHandover(
                'pgj',
                $pgj->id,
                $pgj->area_id,
                $pgj->ticket_number
            );

            $oldOverages = MaterialTransaction::with('items.material')
                ->where('source_type', 'pgj')
                ->where('source_id', $pgj->id)
                ->where('type', 'out')
                ->where('transaction_number', 'like', 'OUT-PGJ-OVERAGE-%')
                ->get();
            foreach ($oldOverages as $oo) {
                if ($oo->status === 'approved' && !$oo->is_migration) {
                    foreach ($oo->items as $ooItem) {
                        if ($ooItem->material && (float) $ooItem->quantity > 0) {
                            \App\Services\MaterialService::restoreOperationalStock(
                                $ooItem->material,
                                (float) $ooItem->quantity,
                                $oo->area_id ?: $pgj->area_id,
                                $ooItem->unit
                            );
                        }
                    }
                }
                $oo->skipStockRestoreOnDelete = true;
                $oo->items()->delete();
                $oo->delete();
            }

            $itemsToReturn = [];
            $overageItems = [];

            foreach ($validated['materials'] as $matItem) {
                $pgjMat = PgjMaterial::find($matItem['pgj_material_id']);
                if ($pgjMat) {
                    $qtyUsed = (float) $matItem['quantity_used'];
                    $qtyPrepared = (float) $pgjMat->quantity_prepared;
                    $qtyReturned = max(0, $qtyPrepared - $qtyUsed);

                    $pgjMat->update([
                        'quantity_used' => $qtyUsed,
                        'quantity_returned' => $qtyReturned,
                    ]);

                    // Jika ada sisa material, catat ke daftar serah terima pengembalian gudang
                    if ($qtyReturned > 0) {
                        $itemsToReturn[] = [
                            'material_id' => $pgjMat->material_id,
                            'quantity' => $qtyReturned,
                            'unit' => $pgjMat->unit ?? 'pcs',
                            'condition' => 'Layak Pakai',
                        ];
                    }

                    // Jika pemakaian aktual melebihi bekal awal, potong selisihnya
                    if ($qtyUsed > $qtyPrepared && $pgjMat->material_id) {
                        $overageItems[] = [
                            'material_id' => $pgjMat->material_id,
                            'quantity' => $qtyUsed - $qtyPrepared,
                            'unit' => $pgjMat->unit ?? 'pcs',
                        ];
                    }
                }
            }

            if (!empty($overageItems)) {
                $ovTrxNum = \App\Services\MaterialService::generateTransactionNumber('OUT-PGJ-OVERAGE');
                $ovTrans = MaterialTransaction::create([
                    'transaction_number' => $ovTrxNum,
                    'user_id' => auth()->id(),
                    'type' => 'out',
                    'status' => 'approved',
                    'request_type' => 'additional_material',
                    'technician_name' => auth()->user()->name,
                    'purpose' => "Selisih Lebih Pemakaian Material PGJ #{$pgj->ticket_number}",
                    'source_type' => 'pgj',
                    'source_id' => $pgj->id,
                    'source_reference' => $pgj->ticket_number,
                    'date' => now(),
                    'area_id' => $pgj->area_id,
                ]);
                $ovCost = 0;
                foreach ($overageItems as $ov) {
                    $ovMat = Material::find($ov['material_id']);
                    if (!$ovMat) continue;
                    $ovStock = \App\Services\MaterialService::deductOperationalStock($ovMat, (float) $ov['quantity'], $pgj->area_id, $ov['unit']);
                    $ppu = \App\Services\MaterialService::calculateUnitPrice($ovMat, $ov['unit']);
                    $ovTotal = $ppu * (float) $ov['quantity'];
                    $ovCost += $ovTotal;
                    MaterialTransactionItem::create([
                        'material_transaction_id' => $ovTrans->id,
                        'material_id' => $ovMat->id,
                        'quantity' => $ov['quantity'],
                        'unit' => $ov['unit'],
                        'price_per_unit' => $ppu,
                        'total_price' => $ovTotal,
                        'stock_before' => $ovStock['stock_before'],
                        'stock_after' => $ovStock['stock_after'],
                    ]);
                }
                $ovTrans->update(['total_cost' => $ovCost]);
            }

            if (!empty($itemsToReturn)) {
                \App\Services\MaterialHandoverService::submitReturnHandover(
                    sourceType: 'pgj',
                    sourceId: $pgj->id,
                    sourceRef: $pgj->ticket_number,
                    items: $itemsToReturn,
                    technicianName: auth()->user()->name,
                    areaId: $pgj->area_id,
                    notes: "Pengembalian Sisa Material Jalur PGJ #{$pgj->ticket_number}"
                );
            }
        }

        $oldStatus = $pgj->status;
        $pgj->update([
            'status' => 'completed',
            'action_taken' => $validated['action_taken'],
            'core_details' => $validated['core_details'] ?? null,
            'optical_loss_before' => $validated['optical_loss_before'] ?? null,
            'optical_loss_after' => $validated['optical_loss_after'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'photo_before' => $photoBeforePath,
            'photo_after' => $photoAfterPath,
            'incident_coordinates' => $incidentCoordsToSave,
            'handling_photos' => !empty($handlingPhotos) ? array_values($handlingPhotos) : null,
            'latitude' => $primaryLat,
            'longitude' => $primaryLng,
            'resolution_notes' => $validated['resolution_notes'] ?? null,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'Laporan Restorasi PGJ Selesai',
            $pgj,
            $oldStatus,
            'completed',
            'Teknisi melaporkan restorasi gangguan jalur selesai: ' . $validated['action_taken']
        );

        return redirect()->back()->with('success', 'Laporan restorasi gangguan jalur berhasil disimpan. Jalur dinyatakan normal kembali.');
    }

    /**
     * Cetak Berita Acara & SPK Restorasi Gangguan Jalur (PGJ)
     */
    public function print(PgjTicket $pgj)
    {
        $pgj->load([
            'area',
            'odc',
            'odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'materialRequests.material',
            'materialRequests.requester',
            'materialRequests.reviewer',
            'resolvedByUser',
        ]);

        return view('print.pgj-report', [
            'ticket' => $pgj,
        ]);
    }

    /**
     * Teknisi mengajukan permintaan tambahan material saat di lapangan (ke Kepala Teknisi)
     */
    public function requestMaterial(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        $canRequest = $user && (
            $user->can('pgj_btn_request_material') ||
            $user->can('pgj_btn_report') ||
            $user->can('pgj_manage') ||
            $user->hasAnyRole(['admin', 'kepala teknisi', 'teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'teknisi', 'teknisi tim serbu', 'tim serbu', 'maintenance']) ||
            str_contains(strtolower($user->role ?? ''), 'teknisi') ||
            str_contains(strtolower($user->role ?? ''), 'serbu')
        );
        if (!$canRequest) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengajukan tambahan material PGJ.');
        }

        // Jika scope 'own', verifikasi bahwa user memang ditugaskan pada PGJ ini
        if ($user && \App\Services\DataScopeService::getScopeLevel($user, 'pgj') === 'own') {
            $isAssigned = $pgj->technicians()->where('users.id', $user->id)->exists()
                || $pgj->created_by === $user->id
                || $pgj->lead_technician_id === $user->id;
            if (!$isAssigned) {
                abort(403, 'Anda hanya dapat mengajukan material pada tugas PGJ yang ditugaskan kepada Anda.');
            }
        }

        // Support both single-item and multi-item array formats
        if ($request->has('requests') && is_array($request->requests)) {
            $validated = $request->validate([
                'requests' => 'required|array|min:1',
                'requests.*.item_name' => 'required|string|max:255',
                'requests.*.material_id' => 'nullable|exists:materials,id',
                'requests.*.quantity' => 'required|numeric|min:0.1',
                'requests.*.unit' => 'required|string|max:50',
                'requests.*.notes' => 'nullable|string',
                'notes' => 'required|string',
            ]);

            $itemsLog = [];
            foreach ($validated['requests'] as $reqItem) {
                PgjMaterialRequest::create([
                    'pgj_ticket_id' => $pgj->id,
                    'item_name' => $reqItem['item_name'],
                    'material_id' => $reqItem['material_id'] ?? null,
                    'requested_by' => auth()->id(),
                    'quantity' => $reqItem['quantity'],
                    'unit' => $reqItem['unit'],
                    'notes' => $reqItem['notes'] ?? $validated['notes'],
                    'status' => 'pending',
                ]);
                $itemsLog[] = "{$reqItem['quantity']} {$reqItem['unit']} {$reqItem['item_name']}";
            }

            AuditLog::createLog(
                'Permintaan Tambahan Material PGJ',
                $pgj,
                $pgj->status,
                $pgj->status,
                "Teknisi mengajukan tambahan material jalur (" . implode(', ', $itemsLog) . ") ke Ka. Teknisi: {$validated['notes']}"
            );

            return redirect()->back()->with('success', "Permintaan tambahan material jalur (" . implode(', ', $itemsLog) . ") berhasil dikirim ke Kepala Teknisi.");
        }

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'material_id' => 'nullable|exists:materials,id',
            'quantity' => 'required|numeric|min:0.1',
            'unit' => 'required|string|max:50',
            'notes' => 'required|string',
        ]);

        PgjMaterialRequest::create([
            'pgj_ticket_id' => $pgj->id,
            'item_name' => $validated['item_name'],
            'material_id' => $validated['material_id'] ?? null,
            'requested_by' => auth()->id(),
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        AuditLog::createLog(
            'Permintaan Tambahan Material PGJ',
            $pgj,
            $pgj->status,
            $pgj->status,
            "Teknisi mengajukan tambahan material jalur ({$validated['quantity']} {$validated['unit']} {$validated['item_name']}) ke Ka. Teknisi: {$validated['notes']}"
        );

        return redirect()->back()->with('success', "Permintaan tambahan {$validated['quantity']} {$validated['unit']} {$validated['item_name']} berhasil dikirim ke Kepala Teknisi.");
    }

    /**
     * Kepala Teknisi menyetujui permintaan tambahan material lapangan & memotong stok gudang
     */
    public function approveMaterialRequest(Request $request, PgjTicket $pgj, PgjMaterialRequest $materialRequest)
    {
        $user = $request->user();
        $canApprove = $user && (
            $user->can('pgj_btn_assign') ||
            $user->can('pgj_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'dispatcher']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'dispatcher'])
        );
        if (!$canApprove) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyetujui permintaan material PGJ.');
        }

        if ((int) $materialRequest->pgj_ticket_id !== (int) $pgj->id) {
            abort(404, 'Permintaan material tidak ditemukan pada tiket PGJ ini.');
        }

        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'material_transaction_item_id' => 'nullable',
            'trx_number' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        return DB::transaction(function () use ($pgj, $materialRequest, $validated) {
            // Kunci baris permintaan agar tidak bisa disetujui dua kali (double click / 2 admin bersamaan)
            $lockedReq = PgjMaterialRequest::where('id', $materialRequest->id)->lockForUpdate()->first();
            if (!$lockedReq || $lockedReq->status !== 'pending') {
                return redirect()->back()->with('error', 'Permintaan material ini sudah pernah diproses sebelumnya.');
            }
            $materialRequest = $lockedReq;

            $material = Material::find($validated['material_id']);
            if (!$material) {
                return redirect()->back()->with('error', 'Data material gudang tidak ditemukan.');
            }

            $qty = (float) $validated['quantity'];
            $unit = $material->unit ?: 'pcs';
            $areaId = $pgj->area_id;

            // Validasi stok gudang (area jika ada, jika tidak gudang pusat) dalam satuan dasar
            $baseQty = \App\Services\MaterialService::convertToBaseQuantity($material, $qty, $unit);
            $availBase = $areaId
                ? (float) (\App\Models\MaterialStock::where('material_id', $material->id)->where('area_id', $areaId)->value('stock') ?? 0)
                : (float) ($material->stock ?? 0);
            if ($availBase + 0.0001 < $baseQty) {
                return redirect()->back()->with('error', "Stok {$material->name} di gudang tidak mencukupi (Tersedia: " . round($availBase, 2) . ", Diminta: {$qty} {$unit}). Lakukan restock / mutasi stok terlebih dahulu.");
            }

            // Potong stok gudang (Area & Master) dengan konversi satuan otomatis
            $stockRes = \App\Services\MaterialService::deductOperationalStock($material, $qty, $areaId, $unit);

            // Buat transaksi pengeluaran material (OUT)
            $requesterName = $materialRequest->requester?->name ?? 'Teknisi Lapangan';
            $trxNumber = \App\Services\MaterialService::generateTransactionNumber('OUT-PGJ-REQ');
            $ppu = \App\Services\MaterialService::calculateUnitPrice($material, $unit);
            $itemTotal = $ppu * $qty;

            $orderRefStr = !empty($validated['trx_number']) ? " (Ref Order/SJ: {$validated['trx_number']})" : '';
            $transaction = MaterialTransaction::create([
                'transaction_number' => $trxNumber,
                'user_id' => auth()->id(),
                'type' => 'out',
                'status' => 'approved',
                'request_type' => 'additional_material',
                'technician_name' => $requesterName,
                'purpose' => "Tambahan Material Lapangan PGJ #{$pgj->ticket_number} ({$pgj->location_name}){$orderRefStr} - Catatan: {$materialRequest->notes}",
                'source_type' => 'pgj',
                'source_id' => $pgj->id,
                'source_reference' => $pgj->ticket_number,
                'date' => now(),
                'total_cost' => $itemTotal,
                'area_id' => $areaId,
            ]);

            MaterialTransactionItem::create([
                'material_transaction_id' => $transaction->id,
                'material_id' => $material->id,
                'quantity' => $qty,
                'unit' => $unit,
                'price_per_unit' => $ppu,
                'total_price' => $itemTotal,
                'stock_before' => $stockRes['stock_before'],
                'stock_after' => $stockRes['stock_after'],
            ]);

            // Tambahkan ke daftar material PGJ (gabungkan hanya jika sudah ada baris tambahan disetujui untuk material yang sama)
            $existingExtraMat = PgjMaterial::where('pgj_ticket_id', $pgj->id)
                ->where('material_id', $material->id)
                ->where('notes', 'like', '%Tambahan disetujui%')
                ->first();

            if ($existingExtraMat) {
                $existingExtraMat->update([
                    'quantity_prepared' => (float) $existingExtraMat->quantity_prepared + $qty,
                    'quantity_used' => (float) $existingExtraMat->quantity_used + $qty,
                    'notes' => trim($existingExtraMat->notes . " | Tambahan disetujui (+{$qty} {$unit}): {$materialRequest->notes}"),
                ]);
            } else {
                PgjMaterial::create([
                    'pgj_ticket_id' => $pgj->id,
                    'material_id' => $material->id,
                    'material_transaction_item_id' => is_numeric($validated['material_transaction_item_id'] ?? null) ? (int) $validated['material_transaction_item_id'] : null,
                    'trx_number' => $validated['trx_number'] ?? null,
                    'quantity_prepared' => $qty,
                    'quantity_used' => $qty,
                    'quantity_returned' => 0,
                    'unit' => $unit,
                    'notes' => 'Tambahan disetujui Ka. Teknisi (Kebutuhan: ' . ($materialRequest->item_name ?: $material->name) . '): ' . ($materialRequest->notes ?? '-'),
                ]);
            }

            $materialRequest->update([
                'material_id' => $material->id,
                'quantity' => $qty,
                'unit' => $unit,
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            AuditLog::createLog(
                'Setujui Tambahan Material PGJ',
                $pgj,
                $pgj->status,
                $pgj->status,
                "Ka. Teknisi menyetujui & menyiapkan tambahan material lapangan ({$qty} {$unit} {$material->name}) untuk {$requesterName}."
            );

            return redirect()->back()->with('success', "Permintaan tambahan {$qty} {$unit} {$material->name} disetujui dan stok gudang telah dikeluarkan untuk teknisi.");
        });
    }

    /**
     * Kepala Teknisi menolak permintaan tambahan material lapangan
     */
    public function rejectMaterialRequest(Request $request, PgjTicket $pgj, PgjMaterialRequest $materialRequest)
    {
        $user = $request->user();
        $canReject = $user && (
            $user->can('pgj_btn_assign') ||
            $user->can('pgj_manage') ||
            $user->hasRole(['admin', 'kepala teknisi', 'dispatcher']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi', 'dispatcher'])
        );
        if (!$canReject) {
            abort(403, 'Anda tidak memiliki hak akses untuk menolak permintaan material PGJ.');
        }

        if ((int) $materialRequest->pgj_ticket_id !== (int) $pgj->id) {
            abort(404, 'Permintaan material tidak ditemukan pada tiket PGJ ini.');
        }

        if ($materialRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Permintaan material ini sudah pernah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $materialRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $matName = $materialRequest->material?->name ?? $materialRequest->item_name ?? 'Material';
        AuditLog::createLog(
            'Tolak Tambahan Material PGJ',
            $pgj,
            $pgj->status,
            $pgj->status,
            "Ka. Teknisi menolak permintaan tambahan {$materialRequest->quantity} {$materialRequest->unit} {$matName}: {$validated['rejection_reason']}"
        );

        return redirect()->back()->with('success', "Permintaan tambahan material {$matName} telah ditolak.");
    }

    /**
     * Hapus Tiket PGJ (Salah Satu)
     */
    public function destroy(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        $canDelete = $user && (
            $user->can('pgj_btn_delete') ||
            $user->can('pgj_manage') ||
            $user->hasRole(['admin', 'kepala teknisi']) ||
            in_array(strtolower($user->role ?? ''), ['admin', 'kepala teknisi'])
        );
        if (!$canDelete) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus tiket PGJ.');
        }

        $num = $pgj->ticket_number;
        $loc = $pgj->location_name;
        $oldStatus = $pgj->status;

        DB::transaction(function () use ($pgj) {
            $this->cleanupAndRestoreTicketStock($pgj);
        });

        AuditLog::createLog(
            'Hapus Tiket PGJ',
            null,
            $oldStatus,
            'deleted',
            "Menghapus tiket PGJ #{$num} ({$loc}) (Stok material dikembalikan ke gudang)"
        );

        return redirect()->back()->with('success', "Tiket PGJ #{$num} berhasil dibatalkan/dihapus dan seluruh material telah dikembalikan ke stok gudang.");
    }

    /**
     * Hapus Massal Tiket PGJ (Terpilih atau Semua)
     */
    public function bulkDestroy(Request $request)
    {
        $user = $request->user();
        $canBulkDelete = $user && (
            $user->can('pgj_btn_delete_all') ||
            $user->can('pgj_btn_delete') ||
            $user->can('pgj_manage') ||
            $user->hasRole('admin') ||
            in_array(strtolower($user->role ?? ''), ['admin'])
        );
        if (!$canBulkDelete) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus massal tiket PGJ.');
        }

        $validated = $request->validate([
            'type' => 'required|in:selected,all',
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
        ]);

        $query = PgjTicket::query();
        DataScopeService::applyPgjScope($query);

        if ($validated['type'] === 'selected') {
            if (empty($validated['ids'])) {
                return redirect()->back()->with('error', 'Tidak ada data tiket PGJ yang dipilih untuk dihapus.');
            }
            $query->whereIn('id', $validated['ids']);
        }

        $count = $query->count();
        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada data tiket PGJ yang dapat dihapus.');
        }

        $records = $query->get();
        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $this->cleanupAndRestoreTicketStock($record);
            }
        });

        AuditLog::createLog(
            'Hapus Massal Tiket PGJ',
            null,
            null,
            'deleted',
            "Menghapus {$count} tiket gangguan jalur PGJ secara massal (Tipe: {$validated['type']}) (Stok material dikembalikan ke gudang)"
        );

        return redirect()->back()->with('success', "Berhasil membatalkan/menghapus {$count} tiket gangguan jalur PGJ dan seluruh material terkait dikembalikan ke gudang.");
    }

    /**
     * Bersihkan data tiket PGJ dan kembalikan stok material ke gudang jika tiket dibatalkan/dihapus
     */
    protected function cleanupAndRestoreTicketStock(PgjTicket $pgj): void
    {
        $areaId = $pgj->area_id;
        $restoredByMat = [];

        // 1. Kembalikan seluruh material pengeluaran (OUT) & balikkan retur (IN) yang sudah diterima secara simetris
        try {
            $restoredByMat = \App\Services\MaterialHandoverService::restoreDispatchedMaterials(
                'pgj',
                $pgj->id,
                $areaId,
                $pgj->ticket_number
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[PgjController::cleanup] Gagal restore material handover: ' . $e->getMessage());
        }

        // 2. Fallback: Hanya jika ada baris pgj_materials lama yang belum punya transaksi OUT
        foreach ($pgj->materials as $pgjMat) {
            $matId = (int) $pgjMat->material_id;
            $qty = (float) ($pgjMat->quantity_prepared ?: $pgjMat->quantity_used);
            if ($qty > 0 && $matId && empty($restoredByMat[$matId])) {
                $material = Material::find($matId);
                if ($material) {
                    \App\Services\MaterialService::restoreOperationalStock($material, $qty, $areaId, $pgjMat->unit);
                }
            }
        }

        // 3. Hapus file foto penanganan jika ada di storage
        if (!empty($pgj->handling_photos) && is_array($pgj->handling_photos)) {
            foreach ($pgj->handling_photos as $p) {
                if (!empty($p['path']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($p['path'])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($p['path']);
                }
            }
        }
        if ($pgj->photo_before && \Illuminate\Support\Facades\Storage::disk('public')->exists($pgj->photo_before)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($pgj->photo_before);
        }
        if ($pgj->photo_after && \Illuminate\Support\Facades\Storage::disk('public')->exists($pgj->photo_after)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($pgj->photo_after);
        }

        $pgj->technicians()->detach();
        $pgj->materials()->delete();
        $pgj->materialRequests()->delete();
        AuditLog::where('entity_type', PgjTicket::class)->where('entity_id', $pgj->id)->delete();
        $pgj->delete();
    }
}
