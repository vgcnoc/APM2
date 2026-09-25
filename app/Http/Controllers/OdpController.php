<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Odp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OdpController extends Controller
{
    public function index(Request $request): Response
    {
        $odps = Odp::with('odc.olt')
            ->withCount('onts')
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%"))
            ->when($request->odc_id, fn ($q, $id) => $q->where('odc_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->available_only, fn ($q) => $q->hasAvailablePort())
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Infrastructure/Odp/Index', [
            'odps' => $odps,
            'odcs' => Odc::with('olt')->where('status', 'active')->get(),
            'filters' => $request->only(['search', 'odc_id', 'status', 'available_only']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'odc_id' => 'required|exists:odcs,id',
            'name' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'total_ports' => 'required|integer|in:4,8,16,32',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,full,maintenance',
        ]);

        $validated['used_ports'] = 0;

        Odp::create($validated);

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
            'name' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'total_ports' => 'required|integer|in:4,8,16,32',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,full,maintenance',
        ]);

        $odp->update($validated);

        return redirect()->route('odps.index')
            ->with('success', 'Data ODP berhasil diperbarui.');
    }

    public function destroy(Odp $odp): RedirectResponse
    {
        $odp->delete();

        return redirect()->route('odps.index')
            ->with('success', 'Data ODP berhasil dihapus.');
    }
}
