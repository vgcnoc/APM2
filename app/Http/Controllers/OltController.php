<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\Ont;
use App\Models\Odc;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OltController extends Controller
{
    /**
     * Daftar Semua OLT (Multi OLT Dashboard)
     */
    public function index(Request $request): Response
    {
        $query = Olt::with(['area'])->withCount('odcs');

        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_olt_view_all')) {
            if (auth()->user()->can('network_olt_view_area')) {
                $query->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                $query->where('id', -1);
            }
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%")
                  ->orWhere('hostname', 'like', "%{$s}%")
                  ->orWhere('brand', 'like', "%{$s}%")
                  ->orWhere('model', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        $olts = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Hitung statistik multi-olt
        $allOlts = Olt::withCount('odcs')->get();
        $stats = [
            'total_olt' => $allOlts->count(),
            'active_olt' => $allOlts->where('status', 'active')->count(),
            'inactive_olt' => $allOlts->where('status', '!=', 'active')->count(),
            'total_pon_ports' => $allOlts->sum('total_pon_ports'),
            'total_odcs' => $allOlts->sum('odcs_count'),
            'total_onts' => Ont::count(),
        ];

        $areas = Area::orderBy('name')->get();
        $brands = Olt::whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand');

        return Inertia::render('Infrastructure/Olt/Index', [
            'olts' => $olts,
            'areas' => $areas,
            'brands' => $brands,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'area_id', 'brand']),
        ]);
    }

    /**
     * Monitoring Port PON Multi OLT
     */
    public function ponMonitor(Request $request): Response
    {
        $query = Olt::with(['area', 'pons.odcs.odps', 'odcs.odps'])
            ->withCount(['odcs', 'pons']);

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $olts = $query->orderBy('name')->get();

        // Hitung utilisasi port PON per OLT
        $olts->each(function ($olt) {
            $olt->total_onts = Ont::whereHas('odp.odc', fn($q) => $q->where('olt_id', $olt->id))->count();
        });

        $areas = Area::orderBy('name')->get();
        $brands = Olt::whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand');

        return Inertia::render('Infrastructure/Olt/PonMonitor', [
            'olts' => $olts,
            'areas' => $areas,
            'brands' => $brands,
            'filters' => $request->only(['area_id', 'brand', 'status']),
        ]);
    }

    /**
     * Peta Sebaran Multi OLT (OLT Geographic Map)
     */
    public function map(Request $request): Response
    {
        $olts = Olt::with(['area', 'odcs.odps'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $olts->each(function ($olt) {
            $olt->total_onts = Ont::whereHas('odp.odc', fn($q) => $q->where('olt_id', $olt->id))->count();
        });

        $areas = Area::orderBy('name')->get();

        return Inertia::render('Infrastructure/Olt/Map', [
            'olts' => $olts,
            'areas' => $areas,
        ]);
    }

    /**
     * Analisis Kapasitas & Utilisasi Jaringan Multi OLT
     */
    public function capacity(Request $request): Response
    {
        $olts = Olt::with(['area', 'pons', 'odcs.odps'])->get();
        $areas = Area::orderBy('name')->get();

        $totalOlt = $olts->count();
        $totalPon = $olts->sum('total_pon_ports');
        $totalCapacitySlots = $olts->sum(fn($o) => $o->total_pon_ports * ($o->pon_capacity ?: 64));
        $totalActiveOnts = Ont::count();

        // Hitung per OLT
        $oltCapacities = $olts->map(function ($olt) {
            $capacity = $olt->total_pon_ports * ($olt->pon_capacity ?: 64);
            $ontCount = Ont::whereHas('odp.odc', fn($q) => $q->where('olt_id', $olt->id))->count();
            $percentage = $capacity > 0 ? round(($ontCount / $capacity) * 100, 1) : 0;

            return [
                'id' => $olt->id,
                'name' => $olt->name,
                'brand' => $olt->brand,
                'model' => $olt->model,
                'area_name' => $olt->area?->name ?? 'N/A',
                'status' => $olt->status,
                'total_pon_ports' => $olt->total_pon_ports,
                'total_capacity' => $capacity,
                'used_capacity' => $ontCount,
                'available_capacity' => max(0, $capacity - $ontCount),
                'percentage' => $percentage,
                'odc_count' => $olt->odcs->count(),
            ];
        });

        return Inertia::render('Infrastructure/Olt/Capacity', [
            'oltCapacities' => $oltCapacities,
            'areas' => $areas,
            'summary' => [
                'total_olt' => $totalOlt,
                'total_pon' => $totalPon,
                'total_capacity' => $totalCapacitySlots,
                'total_used' => $totalActiveOnts,
                'total_available' => max(0, $totalCapacitySlots - $totalActiveOnts),
                'overall_percentage' => $totalCapacitySlots > 0 ? round(($totalActiveOnts / $totalCapacitySlots) * 100, 1) : 0,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'total_pon_ports' => 'required|integer|min:1',
            'pon_capacity' => 'nullable|integer|min:1',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'in:active,inactive,maintenance',
            'description' => 'nullable|string',
            'pon_vlans' => 'nullable|array',
        ]);

        $olt = Olt::create($validated);
        
        // Auto-generate OltPon records
        $capacity = $request->input('pon_capacity', 64);
        for ($i = 1; $i <= $olt->total_pon_ports; $i++) {
            $olt->pons()->create([
                'port_number' => $i,
                'name' => "PON {$i}",
                'capacity' => $capacity,
                'status' => 'active'
            ]);
        }

        return redirect()->route('olts.index')
            ->with('success', 'Data OLT berhasil ditambahkan.');
    }

    public function show(Olt $olt): Response
    {
        $olt->load(['odcs.odps', 'area', 'pons']);

        return Inertia::render('Infrastructure/Olt/Show', [
            'olt' => $olt,
        ]);
    }

    public function update(Request $request, Olt $olt): RedirectResponse
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'total_pon_ports' => 'required|integer|min:1',
            'pon_capacity' => 'nullable|integer|min:1',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'in:active,inactive,maintenance',
            'description' => 'nullable|string',
            'pon_vlans' => 'nullable|array',
        ]);

        $oldPonCount = $olt->total_pon_ports;
        $olt->update($validated);
        
        $capacity = $request->input('pon_capacity', 64);
        
        // Add new PONs if increased
        if ($olt->total_pon_ports > $oldPonCount) {
            for ($i = $oldPonCount + 1; $i <= $olt->total_pon_ports; $i++) {
                $olt->pons()->create([
                    'port_number' => $i,
                    'name' => "PON {$i}",
                    'capacity' => $capacity,
                    'status' => 'active'
                ]);
            }
        }

        if ($request->has('pon_capacity')) {
            $olt->pons()->update(['capacity' => $capacity]);
        }

        return redirect()->route('olts.index')
            ->with('success', 'Data OLT berhasil diperbarui.');
    }

    public function destroy(Olt $olt): RedirectResponse
    {
        $olt->delete();

        return redirect()->route('olts.index')
            ->with('success', 'Data OLT berhasil dihapus.');
    }
}
