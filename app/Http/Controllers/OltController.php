<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OltController extends Controller
{
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

        $olts = $query
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%")
                  ->orWhere('hostname', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $areas = Area::orderBy('name')->get();

        return Inertia::render('Infrastructure/Olt/Index', [
            'olts' => $olts,
            'areas' => $areas,
            'filters' => $request->only(['search', 'status']),
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
        if ($olt->odcs()->count() > 0) {
            return redirect()->route('olts.index')
                ->with('error', 'OLT tidak dapat dihapus karena masih memiliki ' . $olt->odcs()->count() . ' ODC yang terhubung. Hapus atau pindahkan ODC terlebih dahulu.');
        }

        $olt->delete();

        return redirect()->route('olts.index')
            ->with('success', 'Data OLT berhasil dihapus.');
    }
}
