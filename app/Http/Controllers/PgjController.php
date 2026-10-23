<?php

namespace App\Http\Controllers;

use App\Models\PgjTicket;
use App\Models\PgjMaterial;
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

        $technicians = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['teknisi', 'kepala teknisi']);
        })->orWhere('role', 'teknisi')->orderBy('name')->get();

        $leadTechnicians = User::role(['kepala teknisi', 'admin', 'noc', 'manager'])->select('id', 'name')->get();
        if ($leadTechnicians->isEmpty()) {
            $leadTechnicians = User::select('id', 'name')->get();
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

        $technicians = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['teknisi', 'kepala teknisi']);
        })->orWhere('role', 'teknisi')->orderBy('name')->get();

        $materials = Material::with('stocks')
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get();
        $materialTransactions = MaterialTransaction::where('type', 'in')
            ->with(['items.material.stocks', 'areaModel'])
            ->latest('id')
            ->limit(200)
            ->get();

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
        if ($user && !$user->can(['pgj_btn_create', 'pgj_manage']) && !$user->hasRole('admin')) {
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
        if ($user && !$user->can(['pgj_btn_resolve', 'pgj_manage']) && !$user->hasRole('admin')) {
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
        if ($user && !$user->can(['pgj_btn_escalate', 'pgj_manage']) && !$user->hasRole('admin')) {
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
        if ($user && !$user->can(['pgj_btn_assign', 'pgj_manage']) && !$user->hasRole('admin')) {
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

        // Kelola alokasi material gudang jalur
        if (!empty($validated['materials'])) {
            // Kembalikan alokasi material sebelumnya jika ada revisi jadwal
            $previousMaterials = $pgj->materials()->get();
            foreach ($previousMaterials as $prevMat) {
                $prevQty = (float) $prevMat->quantity_prepared;
                if ($prevQty > 0) {
                    if ($pgj->area_id) {
                        $matStock = \App\Models\MaterialStock::where('material_id', $prevMat->material_id)
                            ->where('area_id', $pgj->area_id)
                            ->first();
                        if ($matStock) {
                            $matStock->increment('stock', $prevQty);
                        }
                    }
                    Material::where('id', $prevMat->material_id)->increment('stock', $prevQty);
                }
            }

            $pgj->materials()->delete();

            $techNames = User::whereIn('id', $techIds)->pluck('name')->join(', ');

            $sourceOrders = array_filter(array_unique(array_column($validated['materials'], 'trx_number')));
            $orderRefStr = !empty($sourceOrders) ? ' (Ref Order/SJ: ' . implode(', ', $sourceOrders) . ')' : '';

            $transaction = MaterialTransaction::create([
                'transaction_number' => 'OUT-PGJ-' . date('YmdHis') . rand(10, 99),
                'user_id' => auth()->id(),
                'type' => 'out',
                'status' => 'approved',
                'technician_name' => $techNames ?: auth()->user()->name,
                'purpose' => "Material Jalur PGJ #{$pgj->ticket_number} ({$pgj->segment_type}: {$pgj->category}){$orderRefStr}",
                'source_type' => 'pgj',
                'source_id' => $pgj->id,
                'source_reference' => $pgj->ticket_number,
                'date' => now(),
                'area_id' => $pgj->area_id,
            ]);

            foreach ($validated['materials'] as $matItem) {
                if (empty($matItem['material_id'])) continue;
                $material = Material::find($matItem['material_id']);
                $qty = (float) ($matItem['quantity'] ?? $matItem['quantity_prepared'] ?? 0);
                if ($qty <= 0) continue;

                if ($material) {
                    $stockBefore = (float) $material->stock;
                    $stockAfter = max(0, $stockBefore - $qty);

                    // Kurangkan stok area jika ada
                    if ($pgj->area_id) {
                        $matStock = \App\Models\MaterialStock::where('material_id', $material->id)
                            ->where('area_id', $pgj->area_id)
                            ->first();
                        if ($matStock) {
                            $matStock->decrement('stock', $qty);
                        }
                    }

                    $material->decrement('stock', $qty);

                    $unit = $matItem['unit'] ?? $material->unit;
                    $ppu = $material->price ?? ($material->selling_price ?: ($material->price_per_unit ?? 0));

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $transaction->id,
                        'material_id' => $material->id,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'price_per_unit' => $ppu,
                        'total_price' => $ppu * $qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ]);

                    PgjMaterial::create([
                        'pgj_ticket_id' => $pgj->id,
                        'material_id' => $material->id,
                        'material_transaction_item_id' => $matItem['material_transaction_item_id'] ?? null,
                        'trx_number' => $matItem['trx_number'] ?? null,
                        'quantity_prepared' => $qty,
                        'quantity_used' => 0,
                        'quantity_returned' => 0,
                        'unit' => $unit,
                        'notes' => $matItem['notes'] ?? null,
                    ]);
                }
            }
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
        if ($user && !$user->can(['pgj_btn_report', 'pgj_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melaporkan hasil restorasi PGJ.');
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
            $itemsToReturn = [];

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
                }
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
            'resolvedByUser',
        ]);

        return view('print.pgj-report', [
            'ticket' => $pgj,
        ]);
    }

    /**
     * Hapus Tiket PGJ (Salah Satu)
     */
    public function destroy(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgj_btn_delete', 'pgj_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus tiket PGJ.');
        }

        $num = $pgj->ticket_number;
        $loc = $pgj->location_name;
        $oldStatus = $pgj->status;

        // Hapus file foto penanganan jika ada di storage
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
        AuditLog::where('entity_type', PgjTicket::class)->where('entity_id', $pgj->id)->delete();
        $pgj->delete();

        AuditLog::createLog(
            'Hapus Tiket PGJ',
            null,
            $oldStatus,
            'deleted',
            "Menghapus tiket PGJ #{$num} ({$loc})"
        );

        return redirect()->back()->with('success', "Tiket PGJ #{$num} berhasil dihapus.");
    }

    /**
     * Hapus Massal Tiket PGJ (Terpilih atau Semua)
     */
    public function bulkDestroy(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgj_btn_delete_all', 'pgj_btn_delete', 'pgj_manage']) && !$user->hasRole('admin')) {
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
        foreach ($records as $record) {
            // Hapus file foto
            if (!empty($record->handling_photos) && is_array($record->handling_photos)) {
                foreach ($record->handling_photos as $p) {
                    if (!empty($p['path']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($p['path'])) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($p['path']);
                    }
                }
            }
            if ($record->photo_before && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->photo_before)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($record->photo_before);
            }
            if ($record->photo_after && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->photo_after)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($record->photo_after);
            }

            $record->technicians()->detach();
            $record->materials()->delete();
            AuditLog::where('entity_type', PgjTicket::class)->where('entity_id', $record->id)->delete();
            $record->delete();
        }

        AuditLog::createLog(
            'Hapus Massal Tiket PGJ',
            null,
            null,
            'deleted',
            "Menghapus {$count} tiket gangguan jalur PGJ secara massal (Tipe: {$validated['type']})"
        );

        return redirect()->back()->with('success', "Berhasil menghapus {$count} tiket gangguan jalur PGJ.");
    }
}
