<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Odp;
use App\Services\DataScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OdpController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = Odp::query();
        DataScopeService::applyOdpScope($baseQuery, $user);

        // Options for smart filter dropdowns
        $types = (clone $baseQuery)->whereNotNull('type')->distinct()->orderBy('type')->pluck('type')->filter()->values();

        // 1. Base query for stats and records
        $filteredQuery = (clone $baseQuery);

        // Search Filter (Debounced)
        if ($request->filled('search')) {
            $s = trim($request->search);
            $filteredQuery->where(function ($sub) use ($s) {
                $sub->where('name', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhereHas('odc', fn ($oq) => $oq->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('area', fn ($aq) => $aq->where('name', 'like', "%{$s}%"));
            });
        }

        // Area Filter (Strict & Smart: match odp.area_id first; if null, inherit from odc.area_id)
        if ($request->filled('area_id')) {
            $areaVal = $request->area_id;
            $filteredQuery->where(function ($sub) use ($areaVal) {
                if (is_numeric($areaVal)) {
                    $sub->where('area_id', (int) $areaVal)
                        ->orWhere(function ($fallback) use ($areaVal) {
                            $fallback->whereNull('area_id')
                                ->whereHas('odc', fn($oq) => $oq->where('area_id', (int) $areaVal));
                        });
                } else {
                    $sub->whereHas('area', fn($aq) => $aq->where('name', $areaVal))
                        ->orWhere(function ($fallback) use ($areaVal) {
                            $fallback->whereNull('area_id')
                                ->whereHas('odc.area', fn($aq) => $aq->where('name', $areaVal));
                        });
                }
            });
        }

        // ODC Filter
        if ($request->filled('odc_id')) {
            $filteredQuery->where('odc_id', $request->odc_id);
        }

        // Port Capacity Filter
        if ($request->filled('total_ports')) {
            $filteredQuery->where('total_ports', (int) $request->total_ports);
        }

        // Type Filter (Normal, Split 2, Closure, Pedestal)
        if ($request->filled('type')) {
            $filteredQuery->where('type', $request->type);
        }

        // Date Range Filter
        if ($request->filled('date_from')) {
            $filteredQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $filteredQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // Dynamic Stats calculation scoped to selected area, ODC, search, capacity, type, and date range
        $statsData = (clone $filteredQuery)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            COALESCE(SUM(total_ports), 0) as total_ports,
            COALESCE(SUM(used_ports), 0) as used_ports,
            SUM(CASE WHEN used_ports < total_ports AND (status IS NULL OR status NOT IN ('inactive', 'maintenance')) THEN 1 ELSE 0 END) as has_available,
            SUM(CASE WHEN status = 'full' OR used_ports >= total_ports THEN 1 ELSE 0 END) as full_count,
            SUM(CASE WHEN status IN ('inactive', 'maintenance') THEN 1 ELSE 0 END) as inactive
        ")->first();

        $stats = [
            'total' => (int) ($statsData->total ?? 0),
            'active' => (int) ($statsData->active ?? 0),
            'total_ports' => (int) ($statsData->total_ports ?? 0),
            'used_ports' => (int) ($statsData->used_ports ?? 0),
            'available_ports' => max(0, (int) ($statsData->total_ports ?? 0) - (int) ($statsData->used_ports ?? 0)),
            'has_available' => (int) ($statsData->has_available ?? 0),
            'full' => (int) ($statsData->full_count ?? 0),
            'inactive' => (int) ($statsData->inactive ?? 0),
        ];

        // Table Query (starts from filteredQuery, then applies sub-filters like status and port availability)
        $query = (clone $filteredQuery)->with(['odc.olt', 'area', 'parent:id,name', 'children:id,name'])->withCount('onts');

        // Status Filter
        if ($request->filled('status')) {
            if ($request->status === 'inactive_maintenance') {
                $query->whereIn('status', ['inactive', 'maintenance']);
            } elseif ($request->status === 'has_available') {
                $query->hasAvailablePort();
            } elseif ($request->status === 'full') {
                $query->where(function ($sub) {
                    $sub->where('status', 'full')
                        ->orWhereRaw('used_ports >= total_ports');
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        // Port Availability Filter
        if ($request->filled('port_availability')) {
            if ($request->port_availability === 'available') {
                $query->hasAvailablePort();
            } elseif ($request->port_availability === 'full') {
                $query->whereRaw('used_ports >= total_ports');
            }
        }

        if ($request->boolean('available_only')) {
            $query->hasAvailablePort();
        }

        $perPage = in_array((int) $request->input('per_page'), [10, 15, 25, 50, 100, 250]) ? (int) $request->input('per_page') : 15;

        $odps = $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $odcQuery = Odc::with('olt')->where('status', 'active');
        DataScopeService::applyOdcScope($odcQuery, $user);

        return Inertia::render('Infrastructure/Odp/Index', [
            'odps' => $odps,
            'odcs' => $odcQuery->get(['id', 'name', 'area_id', 'capacity', 'olt_id', 'pon_port', 'splitter_ratio']),
            'areas' => DataScopeService::getScopedAreas($user, 'network_odp'),
            'types' => $types,
            'portOptions' => [4, 8, 16, 32],
            'stats' => $stats,
            'filters' => $request->only([
                'search', 'odc_id', 'area_id', 'status', 'total_ports', 'type',
                'port_availability', 'available_only', 'date_from', 'date_to', 'per_page'
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'odc_id' => 'required|exists:odcs,id',
            'area_id' => 'required|exists:areas,id',
            'name' => 'nullable|string|max:255',
            'type' => 'required|string|in:Normal,Split 2,Closure,Pedestal',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'total_ports' => 'required|integer|in:4,8,16,32',
            'core_capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,full,maintenance',
            'split_units' => 'nullable|array',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $validated['used_ports'] = 0;

        $odc = Odc::findOrFail($validated['odc_id']);
        if ($odc->area_id != $validated['area_id']) {
            return back()->withErrors(['area_id' => 'Area ODP harus sama dengan Area ODC yang dipilih.']);
        }
        
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('odp_photos', 'public');
        }

        if ($validated['type'] === 'Split 2' && !empty($validated['split_units'])) {
            // Save as 2 separate ODPs
            $parentOdpId = null;
            foreach ($validated['split_units'] as $index => $unit) {
                $photoPath = null;
                // Handle file array from split_units
                if ($request->hasFile("split_units.{$index}.photo")) {
                    $photoPath = $request->file("split_units.{$index}.photo")->store('odp_photos', 'public');
                }

                $createdOdp = Odp::create([
                    'odc_id' => $validated['odc_id'],
                    'area_id' => $validated['area_id'],
                    'type' => 'Split 2',
                    'name' => $unit['name'] ?? 'ODP Split',
                    'address' => $unit['address'] ?? null,
                    'latitude' => $unit['latitude'] ?? null,
                    'longitude' => $unit['longitude'] ?? null,
                    'start_point' => $unit['start_point'] ?? null,
                    'end_point' => $unit['end_point'] ?? null,
                    'cable_pull' => $unit['cable_pull'] ?? null,
                    'total_ports' => $unit['ratio'] ?? ($validated['total_ports'] / 2),
                    'used_ports' => 0,
                    'status' => $validated['status'] ?? 'active',
                    'description' => $validated['description'] ?? null,
                    'is_split' => true,
                    'parent_odp_id' => $index > 0 ? $parentOdpId : null,
                    'photo' => $photoPath,
                ]);

                if ($index === 0) {
                    $parentOdpId = $createdOdp->id;
                }
            }
        } else {
            Odp::create($validated);
        }

        return redirect()->route('odps.index')
            ->with('success', 'Data ODP berhasil ditambahkan.');
    }

    public function show(Odp $odp): Response
    {
        $odp->load(['odc.olt', 'onts.customer']);

        return Inertia::render('Infrastructure/Odp/Show', [
            'odp' => $odp,
        ]);
    }

    public function update(Request $request, Odp $odp): RedirectResponse
    {
        $validated = $request->validate([
            'odc_id' => 'required|exists:odcs,id',
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Normal,Split 2,Closure,Pedestal',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'total_ports' => 'required|integer|in:4,8,16,32',
            'core_capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,full,maintenance',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($odp->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($odp->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($odp->photo);
            }
            $validated['photo'] = $request->file('photo')->store('odp_photos', 'public');
        }

        $odc = Odc::findOrFail($validated['odc_id']);
        if ($odc->area_id != $validated['area_id']) {
            return back()->withErrors(['area_id' => 'Area ODP harus sama dengan Area ODC yang dipilih.']);
        }

        $odp->update($validated);

        return redirect()->route('odps.index')
            ->with('success', 'Data ODP berhasil diperbarui.');
    }

    public function destroy(Odp $odp): RedirectResponse
    {
        DB::transaction(function () use ($odp) {
            // Kembalikan ONT yang terhubung menjadi unassigned
            foreach ($odp->onts as $ont) {
                $ont->update([
                    'odp_id' => null, 
                    'odp_port_id' => null,
                    'status' => 'Belum Set/Baru Input',
                    'port_number' => null
                ]);
            }
            // Reset parent_odp_id jika ada ODP split anak
            Odp::where('parent_odp_id', $odp->id)->update(['parent_odp_id' => null]);
            // Hapus port ODP jika ada
            $odp->ports()->delete();
            // Hapus foto jika ada
            if ($odp->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($odp->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($odp->photo);
            }
            $odp->delete();
        });

        return redirect()->route('odps.index')
            ->with('success', 'Data ODP berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $isAll = $request->boolean('all', false);
        $ids = $request->input('ids', []);

        $query = Odp::query();

        // Scope check per role if not admin
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_odp_view_all')) {
            if (auth()->user()->can('network_odp_view_area')) {
                $query->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data ODP.');
            }
        }

        if (!empty($ids) && is_array($ids)) {
            $query->whereIn('id', $ids);
        } elseif (!$isAll) {
            return redirect()->back()->with('error', 'Tidak ada data ODP yang dipilih untuk dihapus.');
        }

        // Jika hapus semua dengan filter
        if ($isAll) {
            if ($request->filled('search')) {
                $s = trim($request->search);
                $query->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('address', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%")
                        ->orWhereHas('odc', fn ($oq) => $oq->where('name', 'like', "%{$s}%"))
                        ->orWhereHas('area', fn ($aq) => $aq->where('name', 'like', "%{$s}%"));
                });
            }
            if ($request->filled('area_id')) {
                $areaVal = $request->area_id;
                $query->where(function ($sub) use ($areaVal) {
                    if (is_numeric($areaVal)) {
                        $sub->where('area_id', (int) $areaVal)
                            ->orWhere(function ($fallback) use ($areaVal) {
                                $fallback->whereNull('area_id')
                                    ->whereHas('odc', fn($oq) => $oq->where('area_id', (int) $areaVal));
                            });
                    } else {
                        $sub->whereHas('area', fn($aq) => $aq->where('name', $areaVal))
                            ->orWhere(function ($fallback) use ($areaVal) {
                                $fallback->whereNull('area_id')
                                    ->whereHas('odc.area', fn($aq) => $aq->where('name', $areaVal));
                            });
                    }
                });
            }
            if ($request->filled('odc_id')) {
                $query->where('odc_id', $request->odc_id);
            }
            if ($request->filled('status')) {
                if ($request->status === 'inactive_maintenance') {
                    $query->whereIn('status', ['inactive', 'maintenance']);
                } elseif ($request->status === 'has_available') {
                    $query->hasAvailablePort();
                } elseif ($request->status === 'full') {
                    $query->where(function ($sub) {
                        $sub->where('status', 'full')
                            ->orWhereRaw('used_ports >= total_ports');
                    });
                } else {
                    $query->where('status', $request->status);
                }
            }
            if ($request->filled('total_ports')) {
                $query->where('total_ports', (int) $request->total_ports);
            }
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('port_availability')) {
                if ($request->port_availability === 'available') {
                    $query->hasAvailablePort();
                } elseif ($request->port_availability === 'full') {
                    $query->whereRaw('used_ports >= total_ports');
                }
            }
            if ($request->boolean('available_only')) {
                $query->hasAvailablePort();
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        }

        $odps = $query->with('onts')->get();
        $count = $odps->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada data ODP yang ditemukan untuk dihapus.');
        }

        DB::transaction(function () use ($odps) {
            foreach ($odps as $odp) {
                // Kembalikan ONT yang terhubung menjadi unassigned
                foreach ($odp->onts as $ont) {
                    $ont->update([
                        'odp_id' => null, 
                        'odp_port_id' => null,
                        'status' => 'Belum Set/Baru Input',
                        'port_number' => null
                    ]);
                }

                // Reset parent_odp_id pada ODP anak (jika split)
                Odp::where('parent_odp_id', $odp->id)->update(['parent_odp_id' => null]);

                // Hapus port ODP jika ada
                $odp->ports()->delete();

                // Hapus foto jika ada
                if ($odp->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($odp->photo)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($odp->photo);
                }

                $odp->delete();
            }
        });

        return redirect()->route('odps.index')
            ->with('success', "Berhasil menghapus {$count} data ODP.");
    }
}
