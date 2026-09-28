<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OltController extends Controller
{
    public function index(Request $request): Response
    {
        $olts = Olt::with(['area'])->withCount('odcs')
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%")
                  ->orWhere('hostname', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $areas = \App\Models\Area::orderBy('name')->get();

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
            'ip_address' => 'nullable|ip',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'total_pon_ports' => 'required|integer|min:1',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'in:active,inactive,maintenance',
            'description' => 'nullable|string',
            'pon_vlans' => 'nullable|array',
        ]);

        Olt::create($validated);

        return redirect()->route('olts.index')
            ->with('success', 'Data OLT berhasil ditambahkan.');
    }

    public function show(Olt $olt): Response
    {
        $olt->load(['odcs.odps']);

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
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'in:active,inactive,maintenance',
            'description' => 'nullable|string',
            'pon_vlans' => 'nullable|array',
        ]);

        $olt->update($validated);

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
