<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Olt;
use App\Services\DataScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OdcController extends Controller
{
    protected function oltServesArea(Olt $olt, $areaId): bool
    {
        if ((int) $olt->area_id === (int) $areaId) {
            return true;
        }

        return $olt->areas()->where('areas.id', $areaId)->exists();
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = Odc::query();
        DataScopeService::applyOdcScope($baseQuery, $user);

        // Stats calculation
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'total_capacity' => (int) (clone $baseQuery)->sum('capacity'),
            'has_odp' => (clone $baseQuery)->has('odps')->count(),
            'no_odp' => (clone $baseQuery)->doesntHave('odps')->count(),
            'inactive' => (clone $baseQuery)->whereIn('status', ['inactive', 'maintenance'])->count(),
        ];

        // Options for smart filter dropdowns
        $capacities = (clone $baseQuery)->whereNotNull('capacity')->distinct()->orderBy('capacity')->pluck('capacity')->filter()->values();
        $types = (clone $baseQuery)->whereNotNull('type')->distinct()->orderBy('type')->pluck('type')->filter()->values();

        $query = (clone $baseQuery)
            ->with([
                'olt',
                'area',
                'parent:id,name,capacity,olt_id,pon_port,type,is_split,parent_odc_id',
                'children:id,name,capacity,olt_id,pon_port,type,is_split,parent_odc_id',
            ])
            ->withCount('odps');

        // Search Filter (Debounced) - sertakan pasangan Split (Induk / Lanjutan) agar tetap berdampingan
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($sub) use ($s) {
                $sub->where('name', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhereHas('parent', fn ($pq) => $pq->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('children', fn ($cq) => $cq->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('olt', fn ($oq) => $oq->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('area', fn ($aq) => $aq->where('name', 'like', "%{$s}%"));
            });
        }

        // Area Filter
        if ($request->filled('area_id')) {
            $areaVal = $request->area_id;
            $query->where(function ($sub) use ($areaVal) {
                if (is_numeric($areaVal)) {
                    $sub->where('area_id', $areaVal);
                } else {
                    $sub->whereHas('area', fn ($aq) => $aq->where('name', $areaVal));
                }
            });
        }

        // OLT Filter
        if ($request->filled('olt_id')) {
            $query->where('olt_id', $request->olt_id);
        }

        // Status Filter
        if ($request->filled('status')) {
            if ($request->status === 'inactive_maintenance') {
                $query->whereIn('status', ['inactive', 'maintenance']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Capacity Filter
        if ($request->filled('capacity')) {
            $query->where('capacity', $request->capacity);
        }

        // Type Filter (Normal, Split, Pedestal, Pole Mount)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // ODP Relation Filter
        if ($request->filled('odp_status')) {
            if ($request->odp_status === 'has_odp') {
                $query->has('odps');
            } elseif ($request->odp_status === 'no_odp') {
                $query->doesntHave('odps');
            }
        }

        // Capacity Range Filter
        if ($request->filled('min_capacity')) {
            $query->where('capacity', '>=', (int) $request->min_capacity);
        }
        if ($request->filled('max_capacity')) {
            $query->where('capacity', '<=', (int) $request->max_capacity);
        }

        // Date Range Filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = in_array((int) $request->input('per_page'), [10, 15, 25, 50, 100, 250]) ? (int) $request->input('per_page') : 15;

        // Urutkan agar pasangan ODC Split (Unit Induk diikuti langsung oleh Unit Lanjutan) selalu berdampingan!
        $odcs = $query
            ->orderByRaw('COALESCE(parent_odc_id, id) DESC, CASE WHEN parent_odc_id IS NULL THEN 0 ELSE 1 END ASC, id ASC')
            ->paginate($perPage)
            ->withQueryString();

        $oltQuery = Olt::where('status', 'active');
        DataScopeService::applyOltScope($oltQuery, $user);

        $parentOdcOptions = (clone $baseQuery)
            ->whereNull('parent_odc_id')
            ->orderBy('name')
            ->get(['id', 'name', 'area_id', 'olt_id', 'pon_port', 'capacity', 'type', 'is_split']);

        return Inertia::render('Infrastructure/Odc/Index', [
            'odcs' => $odcs,
            'olts' => $oltQuery->with('areas:id,name')->get(['id', 'name', 'total_pon_ports', 'area_id', 'pon_vlans']),
            'parentOdcOptions' => $parentOdcOptions,
            'areas' => DataScopeService::getScopedAreas($user, 'network_odc'),
            'capacities' => $capacities,
            'types' => $types,
            'stats' => $stats,
            'filters' => $request->only([
                'search', 'olt_id', 'area_id', 'status', 'capacity', 'type',
                'odp_status', 'min_capacity', 'max_capacity', 'date_from', 'date_to', 'per_page'
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'pon_port' => 'nullable|integer|min:1',
            'splitter_ratio' => 'nullable|string|max:50',
            'area_id' => 'required|exists:areas,id',
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:Normal,Split,Pedestal,Pole Mount,Wall Mount,Mini ODC',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,maintenance',
            'photo' => 'nullable|image|max:2048',
            'split_units' => 'nullable|array',
        ]);

        $olt = Olt::findOrFail($validated['olt_id']);
        if (!$this->oltServesArea($olt, $validated['area_id'])) {
            return back()->withErrors(['area_id' => 'Area ODC harus sesuai dengan Area layanan OLT yang dipilih.']);
        }

        if ($validated['type'] === 'Split' && !empty($validated['split_units'])) {
            $parentOdc = null;
            foreach ($validated['split_units'] as $index => $unit) {
                $unitPhoto = null;
                if ($request->hasFile("split_units.{$index}.photo")) {
                    $unitPhoto = $request->file("split_units.{$index}.photo")->store('odcs', 'public');
                } elseif ($request->hasFile('photo')) {
                    $unitPhoto = $request->file('photo')->store('odcs', 'public');
                }

                $unitName = trim((string) ($unit['name'] ?? '')) ?: ($parentOdc ? 'ODC Lanjutan' : 'ODC Induk');
                if ($parentOdc && strcasecmp($unitName, $parentOdc->name) === 0) {
                    $unitName = "{$unitName} (Lanjutan)";
                }

                $odcData = [
                    'olt_id' => $validated['olt_id'],
                    'pon_port' => $validated['pon_port'] ?? null,
                    'splitter_ratio' => $validated['splitter_ratio'] ?? 'Split 1:2 FBT',
                    'area_id' => $validated['area_id'],
                    'type' => 'Split',
                    'name' => $unitName,
                    'location' => $unit['location'] ?? null,
                    'latitude' => ($unit['latitude'] ?? '') !== '' ? $unit['latitude'] : null,
                    'longitude' => ($unit['longitude'] ?? '') !== '' ? $unit['longitude'] : null,
                    'start_point' => $unit['start_point'] ?? ($parentOdc ? $parentOdc->name : null),
                    'end_point' => $unit['end_point'] ?? $unitName,
                    'cable_pull' => $unit['cable_pull'] ?? 'Individu',
                    'capacity' => max(1, (int) ($unit['ratio'] ?? intdiv((int) $validated['capacity'], 2))),
                    'status' => $validated['status'] ?? 'active',
                    'description' => $validated['description'] ?? null,
                    'photo' => $unitPhoto,
                    'is_split' => true,
                    'parent_odc_id' => $parentOdc ? $parentOdc->id : null,
                ];

                $created = Odc::create($odcData);
                if (!$parentOdc) {
                    $parentOdc = $created;
                }
            }
        } else {
            if ($request->hasFile('photo')) {
                $validated['photo'] = $request->file('photo')->store('odcs', 'public');
            }
            $validated['is_split'] = ($validated['type'] === 'Split');
            Odc::create($validated);
        }

        return redirect()->route('odcs.index')
            ->with('success', 'Data ODC berhasil ditambahkan.');
    }

    public function show(Odc $odc): Response
    {
        $odc->load([
            'olt',
            'area',
            'parent.olt',
            'children.odps',
            'odps' => fn ($q) => $q->with([
                'parent:id,name,parent_odp_id',
                'children:id,name,parent_odp_id',
                'onts.customer',
            ])->orderByRaw('COALESCE(parent_odp_id, id) ASC, CASE WHEN parent_odp_id IS NULL THEN 0 ELSE 1 END ASC, id ASC'),
        ]);

        return Inertia::render('Infrastructure/Odc/Show', [
            'odc' => $odc,
        ]);
    }

    public function update(Request $request, Odc $odc): RedirectResponse
    {
        if ($request->has('parent_odc_id') && ($request->input('parent_odc_id') === '' || $request->input('parent_odc_id') === 'null')) {
            $request->merge(['parent_odc_id' => null]);
        }

        $validated = $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'pon_port' => 'nullable|integer|min:1',
            'splitter_ratio' => 'nullable|string|max:50',
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Normal,Split,Pedestal,Pole Mount,Wall Mount,Mini ODC',
            'parent_odc_id' => 'nullable|exists:odcs,id',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,maintenance',
            'photo' => 'nullable|image|max:2048',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'cable_pull' => 'nullable|string|max:50',
        ]);

        $olt = Olt::findOrFail($validated['olt_id']);
        if (!$this->oltServesArea($olt, $validated['area_id'])) {
            return back()->withErrors(['area_id' => 'Area ODC harus sesuai dengan Area layanan OLT yang dipilih.']);
        }

        if ($validated['type'] !== 'Split') {
            $validated['parent_odc_id'] = null;
            $validated['is_split'] = $odc->children()->exists();
        } else {
            $validated['is_split'] = true;
            if (!empty($validated['parent_odc_id'])) {
                $parentId = (int) $validated['parent_odc_id'];
                if ($parentId === (int) $odc->id) {
                    $validated['parent_odc_id'] = null;
                } else {
                    $childIds = $odc->children()->pluck('id')->map(fn ($id) => (int) $id)->all();
                    if (in_array($parentId, $childIds, true)) {
                        return back()->withErrors(['parent_odc_id' => 'ODC Lanjutan tidak dapat dipilih sebagai ODC Induk (referensi melingkar).']);
                    }
                }
            }
        }

        if ($request->hasFile('photo')) {
            if ($odc->photo) {
                Storage::disk('public')->delete($odc->photo);
            }
            $validated['photo'] = $request->file('photo')->store('odcs', 'public');
        }

        $odc->update($validated);

        return redirect()->route('odcs.index')
            ->with('success', 'Data ODC berhasil diperbarui.');
    }

    public function destroy(Odc $odc): RedirectResponse
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_odc_delete') && !auth()->user()->can('network_odc_delete_all')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data ODC.');
        }

        DB::transaction(function () use ($odc) {
            // Unlink ODP downstream agar aman dari crash foreign key
            \App\Models\Odp::where('odc_id', $odc->id)->update(['odc_id' => null]);

            // Unlink split children
            Odc::where('parent_odc_id', $odc->id)->update(['parent_odc_id' => null]);

            // Hapus foto jika ada
            if ($odc->photo && Storage::disk('public')->exists($odc->photo)) {
                Storage::disk('public')->delete($odc->photo);
            }

            $odc->delete();
        });

        return redirect()->route('odcs.index')
            ->with('success', 'Data ODC berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_odc_delete_all')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus massal data ODC.');
        }

        $ids = $request->input('ids', []);
        $isAll = $request->boolean('all', false);

        $query = Odc::query();

        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_odc_view_all')) {
            if (auth()->user()->can('network_odc_view_area')) {
                $query->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses pada area ini.');
            }
        }

        if (!empty($ids) && is_array($ids)) {
            $query->whereIn('id', $ids);
        } elseif (!$isAll) {
            return redirect()->back()->with('error', 'Tidak ada data ODC yang dipilih untuk dihapus.');
        }

        // Jika mode all, filter berdasarkan filter yang sedang aktif
        if ($isAll) {
            if ($request->filled('search')) {
                $s = trim($request->search);
                $query->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('location', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%")
                        ->orWhereHas('olt', fn ($oq) => $oq->where('name', 'like', "%{$s}%"))
                        ->orWhereHas('area', fn ($aq) => $aq->where('name', 'like', "%{$s}%"));
                });
            }
            if ($request->filled('area_id')) {
                $query->where('area_id', $request->area_id);
            }
            if ($request->filled('olt_id')) {
                $query->where('olt_id', $request->olt_id);
            }
            if ($request->filled('status')) {
                if ($request->status === 'inactive_maintenance') {
                    $query->whereIn('status', ['inactive', 'maintenance']);
                } else {
                    $query->where('status', $request->status);
                }
            }
            if ($request->filled('capacity')) {
                $query->where('capacity', $request->capacity);
            }
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('odp_status')) {
                if ($request->odp_status === 'has_odp') {
                    $query->has('odps');
                } elseif ($request->odp_status === 'no_odp') {
                    $query->doesntHave('odps');
                }
            }
            if ($request->filled('min_capacity')) {
                $query->where('capacity', '>=', (int) $request->min_capacity);
            }
            if ($request->filled('max_capacity')) {
                $query->where('capacity', '<=', (int) $request->max_capacity);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        }

        $odcs = $query->get();
        $count = $odcs->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada data ODC yang ditemukan untuk dihapus.');
        }

        $odcIds = $odcs->pluck('id')->toArray();

        DB::transaction(function () use ($odcs, $odcIds) {
            // Unlink downstream ODP agar tidak rusak
            \App\Models\Odp::whereIn('odc_id', $odcIds)->update(['odc_id' => null]);

            // Unlink split children
            Odc::whereIn('parent_odc_id', $odcIds)->update(['parent_odc_id' => null]);

            // Hapus file foto
            foreach ($odcs as $odc) {
                if ($odc->photo && Storage::disk('public')->exists($odc->photo)) {
                    Storage::disk('public')->delete($odc->photo);
                }
            }

            Odc::whereIn('id', $odcIds)->delete();
        });

        return redirect()->route('odcs.index')
            ->with('success', "Berhasil menghapus {$count} data ODC.");
    }
}
