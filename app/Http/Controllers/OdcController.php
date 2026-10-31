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

        $query = (clone $baseQuery)->with(['olt', 'area'])->withCount('odps');

        // Search Filter (Debounced)
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

        $odcs = $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $oltQuery = Olt::where('status', 'active');
        DataScopeService::applyOltScope($oltQuery, $user);

        return Inertia::render('Infrastructure/Odc/Index', [
            'odcs' => $odcs,
            'olts' => $oltQuery->with('areas:id,name')->get(['id', 'name', 'total_pon_ports', 'area_id', 'pon_vlans']),
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
            'type' => 'required|in:Normal,Split,Pedestal,Pole Mount',
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
        if ($olt->area_id != $validated['area_id']) {
            return back()->withErrors(['area_id' => 'Area ODC harus sama dengan Area OLT yang dipilih.']);
        }

        if ($validated['type'] === 'Split' && !empty($validated['split_units'])) {
            $parentOdc = null;
            foreach ($validated['split_units'] as $unit) {
                $odcData = [
                    'olt_id' => $validated['olt_id'],
                    'pon_port' => $validated['pon_port'] ?? null,
                    'splitter_ratio' => $validated['splitter_ratio'] ?? null,
                    'area_id' => $validated['area_id'],
                    'type' => 'Split',
                    'name' => $unit['name'] ?? 'ODC Split',
                    'location' => $unit['location'] ?? null,
                    'latitude' => $unit['latitude'] ?? null,
                    'longitude' => $unit['longitude'] ?? null,
                    'start_point' => $unit['start_point'] ?? null,
                    'end_point' => $unit['end_point'] ?? null,
                    'cable_pull' => $unit['cable_pull'] ?? null,
                    'capacity' => $unit['ratio'] ?? ($validated['capacity'] / 2),
                    'status' => $validated['status'] ?? 'active',
                    'description' => $validated['description'] ?? null,
                    'is_split' => true,
                    'parent_odc_id' => $parentOdc ? $parentOdc->id : null,
                ];
                
                // Note: File upload for split units is a bit tricky if they are sent in an array. 
                // We'll skip file upload for individual splits via array for now, or assume the parent photo applies.
                if ($request->hasFile('photo')) {
                    $odcData['photo'] = $request->file('photo')->store('odcs', 'public');
                }

                $created = Odc::create($odcData);
                if (!$parentOdc) {
                    $parentOdc = $created;
                }
            }
        } else {
            if ($request->hasFile('photo')) {
                $validated['photo'] = $request->file('photo')->store('odcs', 'public');
            }
            Odc::create($validated);
        }

        return redirect()->route('odcs.index')
            ->with('success', 'Data ODC berhasil ditambahkan.');
    }

    public function show(Odc $odc): Response
    {
        $odc->load(['olt', 'odps.onts.customer']);

        return Inertia::render('Infrastructure/Odc/Show', [
            'odc' => $odc,
        ]);
    }

    public function update(Request $request, Odc $odc): RedirectResponse
    {
        $validated = $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'pon_port' => 'nullable|integer|min:1',
            'splitter_ratio' => 'nullable|string|max:50',
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Normal,Split,Pedestal,Pole Mount',
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
        if ($olt->area_id != $validated['area_id']) {
            return back()->withErrors(['area_id' => 'Area ODC harus sama dengan Area OLT yang dipilih.']);
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
