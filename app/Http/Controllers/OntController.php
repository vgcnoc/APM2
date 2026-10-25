<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\Ont;
use App\Models\Area;
use App\Services\DataScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OntController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = Ont::query();
        DataScopeService::applyOntScope($baseQuery, $user);

        // Hitung statistik untuk cards
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'sudah_set' => (clone $baseQuery)->where('status', 'Sudah Set')->count(),
            'belum_set' => (clone $baseQuery)->where('status', 'Belum Set/Baru Input')->count(),
            'terpasang' => (clone $baseQuery)->whereNotNull('customer_id')->count(),
            'belum_terpasang' => (clone $baseQuery)->whereNull('customer_id')->count(),
            'los' => (clone $baseQuery)->where('status', 'los')->count(),
        ];

        // Ambil daftar brand perangkat yang tersedia
        $brands = (clone $baseQuery)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        $query = (clone $baseQuery)->withFullTopology();

        $onts = $query
            ->when($request->search, function ($q, $s) {
                $q->where(function ($sq) use ($s) {
                    $sq->where('serial_number', 'like', "%{$s}%")
                       ->orWhere('ont_id', 'like', "%{$s}%")
                       ->orWhere('mac_address', 'like', "%{$s}%")
                       ->orWhere('brand', 'like', "%{$s}%")
                       ->orWhere('model', 'like', "%{$s}%")
                       ->orWhere('pppoe_user', 'like', "%{$s}%")
                       ->orWhere('login_user', 'like', "%{$s}%")
                       ->orWhere('ip_login', 'like', "%{$s}%")
                       ->orWhereHas('customer', function ($cq) use ($s) {
                           $cq->where('name', 'like', "%{$s}%")
                              ->orWhere('customer_code', 'like', "%{$s}%")
                              ->orWhere('phone', 'like', "%{$s}%");
                       });
                });
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->installation_status, function ($q, $s) {
                if ($s === 'installed') {
                    $q->whereNotNull('customer_id');
                } elseif ($s === 'uninstalled') {
                    $q->whereNull('customer_id');
                }
            })
            ->when($request->area_id, fn ($q, $id) => $q->where('area_id', $id))
            ->when($request->brand, fn ($q, $b) => $q->where('brand', $b))
            ->when($request->access_mode, fn ($q, $m) => $q->where('access_mode', $m))
            ->when($request->vlan_mode, fn ($q, $vm) => $q->where('vlan_mode', $vm))
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->odp_id, fn ($q, $id) => $q->where('odp_id', $id))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $areas = DataScopeService::getScopedAreas($user, 'network_ont');

        return Inertia::render('Infrastructure/Ont/Index', [
            'onts' => $onts,
            'areas' => $areas,
            'brands' => $brands,
            'stats' => $stats,
            'filters' => $request->only([
                'search',
                'status',
                'installation_status',
                'area_id',
                'brand',
                'access_mode',
                'vlan_mode',
                'date_from',
                'date_to',
                'odp_id',
                'per_page',
            ]),
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
            'free_hotspot' => 'nullable|boolean',
            'hotspot_user' => 'nullable|string',
            'hotspot_password' => 'nullable|string',
            'hotspot_vlan_id' => 'nullable|string',
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

        // ont_id will be auto-generated by the Ont model's boot method if empty
        if (empty($validated['ont_id'])) {
            unset($validated['ont_id']);
        }

        Ont::create($validated);

        if (isset($validated['odp_id']) && isset($validated['port_number'])) {
            $odp = Odp::find($validated['odp_id']);
            if ($odp) {
                $odp->increment('used_ports');
                if ($odp->used_ports >= $odp->total_ports) {
                    $odp->update(['status' => 'full']);
                }
            }

            \App\Models\OdpPort::where('odp_id', $validated['odp_id'])
                ->where('port_number', $validated['port_number'])
                ->update(['status' => 'used']);
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
            'free_hotspot' => 'nullable|boolean',
            'hotspot_user' => 'nullable|string',
            'hotspot_password' => 'nullable|string',
            'hotspot_vlan_id' => 'nullable|string',
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

        $oldOdpId = $ont->odp_id;
        $oldPortNumber = $ont->port_number;
        $newOdpId = $validated['odp_id'] ?? null;
        $newPortNumber = $validated['port_number'] ?? null;

        if ($oldOdpId && $oldPortNumber && ($oldOdpId != $newOdpId || $oldPortNumber != $newPortNumber)) {
            // Release old port
            \App\Models\OdpPort::where('odp_id', $oldOdpId)
                ->where('port_number', $oldPortNumber)
                ->update(['status' => 'available']);
                
            $oldOdp = \App\Models\Odp::find($oldOdpId);
            if ($oldOdp && $oldOdp->used_ports > 0) {
                $oldOdp->decrement('used_ports');
                if ($oldOdp->used_ports - 1 < $oldOdp->total_ports && $oldOdp->status === 'full') {
                    $oldOdp->update(['status' => 'active']);
                }
            }
        }

        $ont->update($validated);

        if ($newOdpId && $newPortNumber && ($oldOdpId != $newOdpId || $oldPortNumber != $newPortNumber)) {
            // Mark new port as used
            \App\Models\OdpPort::where('odp_id', $newOdpId)
                ->where('port_number', $newPortNumber)
                ->update(['status' => 'used']);
                
            $newOdp = \App\Models\Odp::find($newOdpId);
            if ($newOdp) {
                $newOdp->increment('used_ports');
                if ($newOdp->used_ports >= $newOdp->total_ports) {
                    $newOdp->update(['status' => 'full']);
                }
            }
        }

        return redirect()->route('onts.index')
            ->with('success', 'Data ONT berhasil diperbarui.');
    }

    public function destroy(Ont $ont): RedirectResponse
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_ont_delete') && !auth()->user()->can('network_ont_delete_all')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data ONT.');
        }

        $odp = $ont->odp;
        $portNumber = $ont->port_number;
        $transactionItemId = $ont->material_transaction_item_id;
        
        // Update ODP Port status back to available
        if ($odp && $portNumber) {
            \App\Models\OdpPort::where('odp_id', $odp->id)
                ->where('port_number', $portNumber)
                ->update(['status' => 'available']);
        }

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
            ->with('success', 'Data ONT berhasil dihapus, data pelanggan terkait serta seluruh relasinya telah dihapus, dan unit ONT/material telah dikembalikan ke gudang.');
    }


    public function bulkDestroy(Request $request): RedirectResponse
    {
        $isAll = $request->boolean('all', false);
        $ids = $request->input('ids', []);

        // Permission check
        if (auth()->check()) {
            if ($isAll && !auth()->user()->can('network_ont_delete_all') && !auth()->user()->hasRole('admin')) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus semua data ONT.');
            }
            if (!$isAll && !auth()->user()->can('network_ont_delete') && !auth()->user()->can('network_ont_delete_all') && !auth()->user()->hasRole('admin')) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data ONT.');
            }
        }

        $query = Ont::query();

        // Scope check per role if not admin
        DataScopeService::applyOntScope($query, auth()->user());

        if (!empty($ids) && is_array($ids)) {
            $query->whereIn('id', $ids);
        } elseif (!$isAll) {
            return redirect()->back()->with('error', 'Tidak ada data ONT yang dipilih untuk dihapus.');
        }

        // Jika hapus semua dengan filter aktif
        if ($isAll) {
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($sq) use ($s) {
                    $sq->where('serial_number', 'like', "%{$s}%")
                       ->orWhere('ont_id', 'like', "%{$s}%")
                       ->orWhere('mac_address', 'like', "%{$s}%")
                       ->orWhere('brand', 'like', "%{$s}%")
                       ->orWhere('model', 'like', "%{$s}%")
                       ->orWhere('pppoe_user', 'like', "%{$s}%")
                       ->orWhere('login_user', 'like', "%{$s}%")
                       ->orWhere('ip_login', 'like', "%{$s}%")
                       ->orWhereHas('customer', function ($cq) use ($s) {
                           $cq->where('name', 'like', "%{$s}%")
                              ->orWhere('customer_code', 'like', "%{$s}%")
                              ->orWhere('phone', 'like', "%{$s}%");
                       });
                });
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('installation_status')) {
                if ($request->installation_status === 'installed') {
                    $query->whereNotNull('customer_id');
                } elseif ($request->installation_status === 'uninstalled') {
                    $query->whereNull('customer_id');
                }
            }
            if ($request->filled('area_id')) {
                $query->where('area_id', $request->area_id);
            }
            if ($request->filled('brand')) {
                $query->where('brand', $request->brand);
            }
            if ($request->filled('access_mode')) {
                $query->where('access_mode', $request->access_mode);
            }
            if ($request->filled('vlan_mode')) {
                $query->where('vlan_mode', $request->vlan_mode);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            if ($request->filled('odp_id')) {
                $query->where('odp_id', $request->odp_id);
            }
        }

        $onts = $query->get();
        $count = $onts->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada data ONT yang ditemukan untuk dihapus.');
        }

        DB::transaction(function () use ($onts) {
            $transactionItemIds = [];
            $odpsToRecalculate = [];

            foreach ($onts as $ont) {
                // Release ODP Port
                if ($ont->odp_id && $ont->port_number) {
                    \App\Models\OdpPort::where('odp_id', $ont->odp_id)
                        ->where('port_number', $ont->port_number)
                        ->update(['status' => 'available']);
                    
                    $odpsToRecalculate[$ont->odp_id] = true;
                }

                if ($ont->material_transaction_item_id) {
                    $transactionItemIds[$ont->material_transaction_item_id] = true;
                }

                $ont->delete();
            }

            // Recalculate used_ports and status for affected ODPs
            foreach (array_keys($odpsToRecalculate) as $odpId) {
                $odp = Odp::find($odpId);
                if ($odp) {
                    $usedCount = Ont::where('odp_id', $odpId)->count();
                    $odp->used_ports = $usedCount;
                    if ($usedCount < $odp->total_ports && $odp->status === 'full') {
                        $odp->status = 'active';
                    }
                    $odp->save();
                }
            }

            // Reset is_registered_to_ont if no more onts reference it
            foreach (array_keys($transactionItemIds) as $tItemId) {
                $remaining = Ont::where('material_transaction_item_id', $tItemId)->count();
                if ($remaining === 0) {
                    \App\Models\MaterialTransactionItem::where('id', $tItemId)->update(['is_registered_to_ont' => false]);
                }
            }
        });

        return redirect()->route('onts.index')
            ->with('success', "Berhasil menghapus {$count} data ONT. Data pelanggan terkait dan relasinya telah dihapus, serta stok ONT/material telah dikembalikan ke gudang.");
    }


    public function findOnu(Request $request): Response
    {
        $search = $request->query('search');
        $ont = null;

        if ($search) {
            $ont = Ont::withFullTopology()
                ->where('ont_id', $search)
                ->orWhere('serial_number', $search)
                ->orWhere('mac_address', $search)
                ->first();

            if ($ont) {
                $ont->load(['odp.odc.olt', 'customer.package']);
            }
        }

        return Inertia::render('Infrastructure/Ont/FindOnu', [
            'searchQuery' => $search,
            'ont' => $ont,
        ]);
    }
}
