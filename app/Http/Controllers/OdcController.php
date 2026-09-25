<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Olt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OdcController extends Controller
{
    public function index(Request $request): Response
    {
        $odcs = Odc::with('olt')
            ->withCount('odps')
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('location', 'like', "%{$s}%"))
            ->when($request->olt_id, fn ($q, $id) => $q->where('olt_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Infrastructure/Odc/Index', [
            'odcs' => $odcs,
            'olts' => Olt::where('status', 'active')->get(['id', 'name']),
            'filters' => $request->only(['search', 'olt_id', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'name' => 'required|string|max:255',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,maintenance',
        ]);

        Odc::create($validated);

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
            'name' => 'required|string|max:255',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,maintenance',
        ]);

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
