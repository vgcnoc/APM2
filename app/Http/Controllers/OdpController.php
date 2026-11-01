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

        // Search Filter (Debounced) - sertakan pasangan Split 2 (Induk / Lanjutan) agar tetap berdampingan
        if ($request->filled('search')) {
            $s = trim($request->search);
            $filteredQuery->where(function ($sub) use ($s) {
                $sub->where('name', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhereHas('parent', fn ($pq) => $pq->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('children', fn ($cq) => $cq->where('name', 'like', "%{$s}%"))
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
        $query = (clone $filteredQuery)->with([
            'odc.olt',
            'odc.parent:id,name,parent_odc_id',
            'odc.children:id,name,parent_odc_id',
            'area',
            'parent:id,name,total_ports,used_ports,odc_id,parent_odp_id',
            'children:id,name,total_ports,used_ports,odc_id,parent_odp_id',
        ])->withCount('onts');

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

        // Urutkan agar pasangan ODP Split 2 (Unit Induk diikuti langsung oleh Unit Lanjutan) selalu berdampingan!
        $odps = $query
            ->orderByRaw('COALESCE(parent_odp_id, id) DESC, CASE WHEN parent_odp_id IS NULL THEN 0 ELSE 1 END ASC, id ASC')
            ->paginate($perPage)
            ->withQueryString();

        $odcQuery = Odc::with('olt')->where('status', 'active');
        DataScopeService::applyOdcScope($odcQuery, $user);

        $parentOdpOptions = (clone $baseQuery)
            ->whereNull('parent_odp_id')
            ->orderBy('name')
            ->get(['id', 'name', 'odc_id', 'area_id', 'total_ports', 'type', 'is_split']);

        return Inertia::render('Infrastructure/Odp/Index', [
            'odps' => $odps,
            'odcs' => $odcQuery->get(['id', 'name', 'area_id', 'capacity', 'olt_id', 'pon_port', 'splitter_ratio', 'type', 'is_split', 'parent_odc_id']),
            'parentOdpOptions' => $parentOdpOptions,
            'areas' => DataScopeService::getScopedAreas($user, 'network_odp'),
            'types' => $types,
            'portOptions' => [2, 4, 8, 12, 16, 24, 32, 64],
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
            'type' => 'required|string|in:Normal,Split 2,Closure,Pedestal,Pole Mount,Wall Mount,FAT Mini,Inline Closure',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'total_ports' => 'required|integer|min:1|max:128',
            'core_capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,full,maintenance',
            'split_units' => 'nullable|array',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
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
            // Simpan sebagai 2 unit ODP terpisah namun saling bertautan (Induk -> Lanjutan)
            $parentOdp = null;
            foreach ($validated['split_units'] as $index => $unit) {
                $photoPath = $validated['photo'] ?? null;
                if ($request->hasFile("split_units.{$index}.photo")) {
                    $photoPath = $request->file("split_units.{$index}.photo")->store('odp_photos', 'public');
                }

                $unitName = trim((string) ($unit['name'] ?? '')) ?: ($parentOdp ? 'ODP Lanjutan' : 'ODP Induk');
                if ($parentOdp && strcasecmp($unitName, $parentOdp->name) === 0) {
                    $unitName = "{$unitName} (Lanjutan)";
                }

                $createdOdp = Odp::create([
                    'odc_id' => $validated['odc_id'],
                    'area_id' => $validated['area_id'],
                    'type' => 'Split 2',
                    'name' => $unitName,
                    'address' => $unit['address'] ?? null,
                    'latitude' => ($unit['latitude'] ?? '') !== '' ? $unit['latitude'] : null,
                    'longitude' => ($unit['longitude'] ?? '') !== '' ? $unit['longitude'] : null,
                    'start_point' => $unit['start_point'] ?? ($parentOdp ? $parentOdp->name : $odc->name),
                    'end_point' => $unit['end_point'] ?? $unitName,
                    'cable_pull' => $unit['cable_pull'] ?? 'Individu',
                    'total_ports' => max(1, (int) ($unit['ratio'] ?? intdiv((int) $validated['total_ports'], 2))),
                    'core_capacity' => $validated['core_capacity'] ?? null,
                    'used_ports' => 0,
                    'status' => $validated['status'] ?? 'active',
                    'description' => $validated['description'] ?? null,
                    'is_split' => true,
                    'parent_odp_id' => $parentOdp ? $parentOdp->id : null,
                    'photo' => $photoPath,
                ]);

                if (!$parentOdp) {
                    $parentOdp = $createdOdp;
                }
            }
        } else {
            $validated['is_split'] = ($validated['type'] === 'Split 2');
            Odp::create($validated);
        }

        return redirect()->route('odps.index')
            ->with('success', 'Data ODP berhasil ditambahkan.');
    }

    public function show(Odp $odp): Response
    {
        $odp->load([
            'odc.olt',
            'odc.parent:id,name,parent_odc_id',
            'odc.children:id,name,parent_odc_id',
            'area',
            'parent',
            'children',
            'onts.customer',
        ]);

        return Inertia::render('Infrastructure/Odp/Show', [
            'odp' => $odp,
        ]);
    }

    public function update(Request $request, Odp $odp): RedirectResponse
    {
        if ($request->has('parent_odp_id') && ($request->input('parent_odp_id') === '' || $request->input('parent_odp_id') === 'null')) {
            $request->merge(['parent_odp_id' => null]);
        }

        $validated = $request->validate([
            'odc_id' => 'required|exists:odcs,id',
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Normal,Split 2,Closure,Pedestal,Pole Mount,Wall Mount,FAT Mini,Inline Closure',
            'parent_odp_id' => 'nullable|exists:odps,id',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'total_ports' => 'required|integer|min:1|max:128',
            'core_capacity' => 'nullable|integer|min:1',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'cable_pull' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,full,maintenance',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($odp->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($odp->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($odp->photo);
            }
            $validated['photo'] = $request->file('photo')->store('odp_photos', 'public');
        }

        $odc = Odc::findOrFail($validated['odc_id']);
        if ($odc->area_id != $validated['area_id']) {
            return back()->withErrors(['area_id' => 'Area ODP harus sama dengan Area ODC yang dipilih.']);
        }

        if ($validated['type'] !== 'Split 2') {
            $validated['parent_odp_id'] = null;
            $validated['is_split'] = $odp->children()->exists();
        } else {
            $validated['is_split'] = true;
            if (!empty($validated['parent_odp_id'])) {
                $parentId = (int) $validated['parent_odp_id'];
                if ($parentId === (int) $odp->id) {
                    $validated['parent_odp_id'] = null;
                } else {
                    $childIds = $odp->children()->pluck('id')->map(fn ($id) => (int) $id)->all();
                    if (in_array($parentId, $childIds, true)) {
                        return back()->withErrors(['parent_odp_id' => 'ODP Lanjutan tidak dapat dipilih sebagai ODP Induk (referensi melingkar).']);
                    }
                }
            }
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
