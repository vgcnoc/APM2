<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\Ont;
use App\Models\Area;
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
                  ->orWhere('ont_id', 'like', "%{$s}%")
                  ->orWhere('mac_address', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn ($cq) =>
                      $cq->where('name', 'like', "%{$s}%")
                         ->orWhere('customer_code', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->odp_id, fn ($q, $id) => $q->where('odp_id', $id))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $areas = Area::all();

        return Inertia::render('Infrastructure/Ont/Index', [
            'onts' => $onts,
            'areas' => $areas,
            'filters' => $request->only(['search', 'status', 'odp_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ont_id' => 'nullable|string|max:100|unique:onts,ont_id',
            'area_id' => 'nullable|exists:areas,id',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'required|string|unique:onts,serial_number',
            'mac_address' => 'nullable|string|max:20',
            'vlan_mode' => 'nullable|string',
            'vlan_id' => 'nullable|string',
            'access_mode' => 'nullable|string',
            'ip_login' => 'nullable|string',
            'login_user' => 'nullable|string',
            'login_password' => 'nullable|string',
            'pppoe_user' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'input_officers' => 'nullable|array',
            'status' => 'nullable|string',
            
            // These can be empty on inventory input
            'odp_id' => 'nullable|exists:odps,id',
            'customer_id' => 'nullable|exists:customers,id',
            'port_number' => 'nullable|integer|min:1',
            'rx_power' => 'nullable|numeric',
            'tx_power' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['customer_id'])) {
            $validated['odp_id'] = null;
            $validated['port_number'] = null;
        }

        if (empty($validated['ont_id'])) {
            // Generate auto ont_id like ONT-YYYYMMDD-XXXX
            $validated['ont_id'] = 'ONT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        }

        Ont::create($validated);

        if (isset($validated['odp_id'])) {
            Odp::find($validated['odp_id'])->increment('used_ports');
        }

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
            'ont_id' => "nullable|string|max:100|unique:onts,ont_id,{$ont->id}",
            'area_id' => 'nullable|exists:areas,id',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => "required|string|unique:onts,serial_number,{$ont->id}",
            'mac_address' => 'nullable|string|max:20',
            'vlan_mode' => 'nullable|string',
            'vlan_id' => 'nullable|string',
            'access_mode' => 'nullable|string',
            'ip_login' => 'nullable|string',
            'login_user' => 'nullable|string',
            'login_password' => 'nullable|string',
            'pppoe_user' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'input_officers' => 'nullable|array',
            'status' => 'nullable|string',
            
            // These can be empty on inventory input
            'odp_id' => 'nullable|exists:odps,id',
            'customer_id' => 'nullable|exists:customers,id',
            'port_number' => 'nullable|integer|min:1',
            'rx_power' => 'nullable|numeric',
            'tx_power' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['customer_id'])) {
            $validated['odp_id'] = null;
            $validated['port_number'] = null;
        }

        $ont->update($validated);

        return redirect()->route('onts.index')
            ->with('success', 'Data ONT berhasil diperbarui.');
    }

    public function destroy(Ont $ont): RedirectResponse
    {
        $odp = $ont->odp;
        $transactionItemId = $ont->material_transaction_item_id;
        
        $ont->delete();

        // Decrement port terpakai di ODP
        if ($odp) {
            if ($odp->used_ports > 0) {
                $odp->decrement('used_ports');
            }
            if ($odp->status === 'full') {
                $odp->update(['status' => 'active']);
            }
        }
        
        if ($transactionItemId) {
            $remaining = Ont::where('material_transaction_item_id', $transactionItemId)->count();
            if ($remaining === 0) {
                \App\Models\MaterialTransactionItem::where('id', $transactionItemId)->update(['is_registered_to_ont' => false]);
            }
        }

        return redirect()->route('onts.index')
            ->with('success', 'Data ONT berhasil dihapus.');
    }
}
