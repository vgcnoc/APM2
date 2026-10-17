<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Odp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OdpController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = Odp::query();

        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_odp_view_all')) {
            if (auth()->user()->can('network_odp_view_area')) {
                $baseQuery->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                $baseQuery->where('id', -1);
            }
        }

        // Stats calculation
        $totalOdps = (clone $baseQuery)->count();
        $activeOdps = (clone $baseQuery)->where('status', 'active')->count();
        $totalPorts = (int) (clone $baseQuery)->sum('total_ports');
        $usedPorts = (int) (clone $baseQuery)->sum('used_ports');
        $availablePorts = max(0, $totalPorts - $usedPorts);
        $odpsWithAvailable = (clone $baseQuery)->hasAvailablePort()->count();
        $fullOdps = (clone $baseQuery)->where(function ($q) {
            $q->where('status', 'full')
              ->orWhereRaw('used_ports >= total_ports');
        })->count();
        $inactiveOdps = (clone $baseQuery)->whereIn('status', ['inactive', 'maintenance'])->count();

        $stats = [
            'total' => $totalOdps,
            'active' => $activeOdps,
            'total_ports' => $totalPorts,
            'used_ports' => $usedPorts,
            'available_ports' => $availablePorts,
            'has_available' => $odpsWithAvailable,
            'full' => $fullOdps,
            'inactive' => $inactiveOdps,
        ];

        // Options for smart filter dropdowns
        $types = (clone $baseQuery)->whereNotNull('type')->distinct()->orderBy('type')->pluck('type')->filter()->values();

        $query = (clone $baseQuery)->with(['odc.olt', 'area'])->withCount('onts');

        // Search Filter (Debounced)
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

        // Area Filter
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        // ODC Filter
        if ($request->filled('odc_id')) {
            $query->where('odc_id', $request->odc_id);
        }

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

        // Port Capacity Filter
        if ($request->filled('total_ports')) {
            $query->where('total_ports', (int) $request->total_ports);
        }

        // Type Filter (Normal, Split 2, Closure, Pedestal)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
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

        // Date Range Filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = in_array((int) $request->input('per_page'), [10, 15, 25, 50, 100, 250]) ? (int) $request->input('per_page') : 15;

        $odps = $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Infrastructure/Odp/Index', [
            'odps' => $odps,
            'odcs' => Odc::with('olt')->where('status', 'active')->get(['id', 'name', 'area_id', 'capacity', 'olt_id', 'pon_port', 'splitter_ratio']),
            'areas' => \App\Models\Area::orderBy('name')->get(['id', 'name']),
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
            foreach ($validated['split_units'] as $index => $unit) {
                $photoPath = null;
                // Handle file array from split_units
                if ($request->hasFile("split_units.{$index}.photo")) {
                    $photoPath = $request->file("split_units.{$index}.photo")->store('odp_photos', 'public');
                }

                Odp::create([
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
                    'photo' => $photoPath,
                ]);
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
                $query->where('area_id', $request->area_id);
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
