<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\Area;
use App\Services\DataScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OltController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = Olt::query();
        DataScopeService::applyOltScope($baseQuery, $user);

        // Stats calculation
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'total_pon_ports' => (clone $baseQuery)->sum('total_pon_ports') ?: 0,
            'has_odc' => (clone $baseQuery)->has('odcs')->count(),
            'inactive' => (clone $baseQuery)->whereIn('status', ['inactive', 'maintenance'])->count(),
        ];

        // Brands available
        $brands = (clone $baseQuery)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        $query = (clone $baseQuery)->with(['area'])->withCount('odcs');

        $olts = $query
            ->when($request->search, function ($q, $s) {
                $q->where(function ($sq) use ($s) {
                    $sq->where('name', 'like', "%{$s}%")
                       ->orWhere('ip_address', 'like', "%{$s}%")
                       ->orWhere('hostname', 'like', "%{$s}%")
                       ->orWhere('brand', 'like', "%{$s}%")
                       ->orWhere('model', 'like', "%{$s}%")
                       ->orWhere('location', 'like', "%{$s}%");
                });
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->area_id, fn ($q, $id) => $q->where('area_id', $id))
            ->when($request->brand, fn ($q, $b) => $q->where('brand', $b))
            ->when($request->pon_ports, fn ($q, $p) => $q->where('total_pon_ports', $p))
            ->when($request->odc_status, function ($q, $os) {
                if ($os === 'has_odc') {
                    $q->has('odcs');
                } elseif ($os === 'no_odc') {
                    $q->doesntHave('odcs');
                }
            })
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $areas = DataScopeService::getScopedAreas($user, 'network_olt');

        return Inertia::render('Infrastructure/Olt/Index', [
            'olts' => $olts,
            'areas' => $areas,
            'brands' => $brands,
            'stats' => $stats,
            'filters' => $request->only([
                'search',
                'status',
                'area_id',
                'brand',
                'pon_ports',
                'odc_status',
                'date_from',
                'date_to',
                'per_page',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:255',
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

        if (isset($validated['ip_address'])) {
            $validated['ip_address'] = trim((string)$validated['ip_address']) ?: null;
        }

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
            'ip_address' => 'nullable|string|max:255',
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

        if (isset($validated['ip_address'])) {
            $validated['ip_address'] = trim((string)$validated['ip_address']) ?: null;
        }

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
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_olt_delete') && !auth()->user()->can('network_olt_delete_all')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data OLT.');
        }

        if ($olt->odcs()->count() > 0 && !request()->boolean('force')) {
            return redirect()->route('olts.index')
                ->with('error', 'OLT tidak dapat dihapus karena masih memiliki ' . $olt->odcs()->count() . ' ODC yang terhubung. Hapus atau pindahkan ODC terlebih dahulu.');
        }

        $olt->pons()->delete();
        $olt->delete();

        return redirect()->route('olts.index')
            ->with('success', 'Data OLT berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $isAll = $request->boolean('all', false);
        $ids = $request->input('ids', []);

        // Permission check
        if (auth()->check()) {
            if ($isAll && !auth()->user()->can('network_olt_delete_all') && !auth()->user()->hasRole('admin')) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus semua data OLT.');
            }
            if (!$isAll && !auth()->user()->can('network_olt_delete') && !auth()->user()->can('network_olt_delete_all') && !auth()->user()->hasRole('admin')) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data OLT.');
            }
        }

        $query = Olt::query();

        // Scope check per role if not admin
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_olt_view_all')) {
            if (auth()->user()->can('network_olt_view_area')) {
                $query->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses wilayah untuk menghapus data OLT.');
            }
        }

        if (!empty($ids) && is_array($ids)) {
            $query->whereIn('id', $ids);
        } elseif (!$isAll) {
            return redirect()->back()->with('error', 'Tidak ada data OLT yang dipilih untuk dihapus.');
        }

        // If all with active filters
        if ($isAll) {
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($sq) use ($s) {
                    $sq->where('name', 'like', "%{$s}%")
                       ->orWhere('ip_address', 'like', "%{$s}%")
                       ->orWhere('hostname', 'like', "%{$s}%")
                       ->orWhere('brand', 'like', "%{$s}%")
                       ->orWhere('model', 'like', "%{$s}%")
                       ->orWhere('location', 'like', "%{$s}%");
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
            if ($request->filled('pon_ports')) {
                $query->where('total_pon_ports', $request->pon_ports);
            }
            if ($request->filled('odc_status')) {
                if ($request->odc_status === 'has_odc') {
                    $query->has('odcs');
                } elseif ($request->odc_status === 'no_odc') {
                    $query->doesntHave('odcs');
                }
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        }

        $olts = $query->get();
        $count = $olts->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada data OLT yang ditemukan untuk dihapus.');
        }

        DB::transaction(function () use ($olts) {
            foreach ($olts as $olt) {
                // Delete pons
                $olt->pons()->delete();
                $olt->delete();
            }
        });

        return redirect()->route('olts.index')
            ->with('success', "Berhasil menghapus {$count} data OLT.");
    }
}
