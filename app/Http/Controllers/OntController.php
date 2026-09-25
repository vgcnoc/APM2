<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\Ont;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OntController extends Controller
{
    public function index(Request $request): Response
    {
        $onts = Ont::withFullTopology()
            ->when($request->search, fn ($q, $s) =>
                $q->where('serial_number', 'like', "%{$s}%")
                  ->orWhere('mac_address', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn ($cq) =>
                      $cq->where('name', 'like', "%{$s}%")
                         ->orWhere('customer_code', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->odp_id, fn ($q, $id) => $q->where('odp_id', $id))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Infrastructure/Ont/Index', [
            'onts' => $onts,
            'filters' => $request->only(['search', 'status', 'odp_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'odp_id' => 'required|exists:odps,id',
            'customer_id' => 'nullable|exists:customers,id',
            'serial_number' => 'required|string|unique:onts,serial_number',
            'mac_address' => 'nullable|string|max:17',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'port_number' => 'required|integer|min:1',
            'rx_power' => 'nullable|numeric',
            'tx_power' => 'nullable|numeric',
            'status' => 'in:active,inactive,los,damaged',
            'description' => 'nullable|string',
        ]);

        Ont::create($validated);

        // Increment port terpakai di ODP
        Odp::find($validated['odp_id'])->increment('used_ports');

        return redirect()->route('onts.index')
            ->with('success', 'Data ONT berhasil ditambahkan.');
    }

    public function show(Ont $ont): Response
    {
        $ont->load(['odp.odc.olt', 'customer.package']);

        return Inertia::render('Infrastructure/Ont/Show', [
            'ont' => $ont,
        ]);
    }

    public function update(Request $request, Ont $ont): RedirectResponse
    {
        $validated = $request->validate([
            'odp_id' => 'required|exists:odps,id',
            'customer_id' => 'nullable|exists:customers,id',
            'serial_number' => "required|string|unique:onts,serial_number,{$ont->id}",
            'mac_address' => 'nullable|string|max:17',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'port_number' => 'required|integer|min:1',
            'rx_power' => 'nullable|numeric',
            'tx_power' => 'nullable|numeric',
            'status' => 'in:active,inactive,los,damaged',
            'description' => 'nullable|string',
        ]);

        $ont->update($validated);

        return redirect()->route('onts.index')
            ->with('success', 'Data ONT berhasil diperbarui.');
    }

    public function destroy(Ont $ont): RedirectResponse
    {
        $odp = $ont->odp;
        $ont->delete();

        // Decrement port terpakai di ODP
        if ($odp) {
            $odp->decrement('used_ports');
            if ($odp->status === 'full') {
                $odp->update(['status' => 'active']);
            }
        }

        return redirect()->route('onts.index')
            ->with('success', 'Data ONT berhasil dihapus.');
    }
}
