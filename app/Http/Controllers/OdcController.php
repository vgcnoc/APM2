<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Olt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OdcController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Odc::with(['olt', 'area'])->withCount('odps');

        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_odc_view_all')) {
            if (auth()->user()->can('network_odc_view_area')) {
                $query->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                $query->where('id', -1);
            }
        }

        $odcs = $query
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('location', 'like', "%{$s}%"))
            ->when($request->olt_id, fn ($q, $id) => $q->where('olt_id', $id))
            ->when($request->area_id, fn ($q, $id) => $q->where('area_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Infrastructure/Odc/Index', [
            'odcs' => $odcs,
            'olts' => Olt::where('status', 'active')->get(['id', 'name', 'total_pon_ports', 'area_id']),
            'areas' => \App\Models\Area::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'olt_id', 'area_id', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'pon_port' => 'nullable|integer|min:1',
            'area_id' => 'required|exists:areas,id',
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:Normal,Split',
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
            foreach ($validated['split_units'] as $unit) {
                $odcData = [
                    'olt_id' => $validated['olt_id'],
                    'pon_port' => $validated['pon_port'] ?? null,
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
                ];
                
                // Note: File upload for split units is a bit tricky if they are sent in an array. 
                // We'll skip file upload for individual splits via array for now, or assume the parent photo applies.
                if ($request->hasFile('photo')) {
                    $odcData['photo'] = $request->file('photo')->store('odcs', 'public');
                }

                Odc::create($odcData);
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
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Normal,Split',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,maintenance',
            'photo' => 'nullable|image|max:2048',
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
        $odc->delete();

        return redirect()->route('odcs.index')
            ->with('success', 'Data ODC berhasil dihapus.');
    }
}
