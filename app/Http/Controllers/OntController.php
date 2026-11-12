<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\Ont;
use App\Models\Area;
use App\Models\Setting;
use App\Services\DataScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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

        if (\App\Services\DataPrivacyMaskingService::isEnabledForUser($user, 'onts')) {
            $maskedCustomerIds = \App\Services\DataPrivacyMaskingService::getMaskedCustomerIds();
            $baseQuery->where(function($q) use ($maskedCustomerIds) {
                $q->whereNull('customer_id')
                  ->orWhereIn('customer_id', !empty($maskedCustomerIds) ? $maskedCustomerIds : [0]);
            });
        }

        // Stats kontekstual yang cerdas & sinkron dengan filter Cabang (Area)
        $statsQuery = (clone $baseQuery)->when($request->area_id, fn ($q, $id) => $q->where('area_id', $id));

        // Hitung statistik untuk cards & tabs (cerdas, presisi & sinkron)
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'sudah_set' => (clone $statsQuery)->where('status', 'Sudah Set')->whereNull('customer_id')->count(),
            'belum_set' => (clone $statsQuery)->where('status', 'Belum Set/Baru Input')->whereNull('customer_id')->count(),
            'terpasang' => (clone $statsQuery)->whereNotNull('customer_id')->count(),
            'belum_terpasang' => (clone $statsQuery)->whereNull('customer_id')->count(),
            'los' => (clone $statsQuery)->where('status', 'los')->count(),
            'rusak' => (clone $statsQuery)->whereIn('status', ['rusak', 'damaged'])->count(),
            'ont_diganti' => (clone $statsQuery)->diganti()->count(),
            'ont_pengganti' => (clone $statsQuery)->pengganti()->count(),
        ];

        // Ambil daftar brand perangkat yang tersedia sesuai area yang dipilih
        $brands = (clone $statsQuery)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        $query = (clone $baseQuery)->withFullTopology()->with([
            'pgsTicketsReplaced.customer',
            'pgsTicketsReplaced.preparedOnt',
            'pgkTicketsReplaced.customer',
            'pgkTicketsReplaced.preparedOnt',
            'pgsTicketsPrepared.customer',
            'pgsTicketsPrepared.oldOnt',
            'pgkTicketsPrepared.customer',
            'pgkTicketsPrepared.oldOnt',
        ]);

        $onts = $query
            ->when($request->tab, function ($q, $tab) {
                if ($tab === 'ont_diganti') {
                    $q->diganti();
                } elseif ($tab === 'ont_pengganti') {
                    $q->pengganti();
                }
            })
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
            ->when($request->status, function ($q, $s) {
                if ($s === 'rusak') {
                    $q->whereIn('status', ['rusak', 'damaged']);
                } else {
                    $q->where('status', $s);
                }
            })
            ->when($request->installation_status, function ($q, $s) {
                if ($s === 'installed') {
                    $q->whereNotNull('customer_id');
                } elseif ($s === 'uninstalled') {
                    $q->whereNull('customer_id');
                }
            })
            ->when($request->area_id, fn ($q, $id) => $q->where('area_id', $id))
            ->when($request->brand, fn ($q, $b) => $q->where('brand', $b))
            ->when($request->access_mode, function ($q, $m) {
                $upper = strtoupper(trim($m));
                if (str_contains($upper, 'HOTSPOT') || str_contains($upper, 'MEMBER')) {
                    $q->where(function ($sq) {
                        $sq->whereIn('access_mode', ['hotspot', 'Hotspot', 'HOTSPOT', 'HOTSPOT/MEMBER', 'MEMBER'])
                           ->orWhere('access_mode', 'LIKE', '%HOTSPOT%');
                    });
                } elseif (str_contains($upper, 'STATIC')) {
                    $q->where(function ($sq) {
                        $sq->whereIn('access_mode', ['static_ip', 'static', 'STATIC', 'Static'])
                           ->orWhere('access_mode', 'LIKE', '%STATIC%');
                    });
                } else {
                    $q->where('access_mode', $m);
                }
            })
            ->when($request->vlan_mode, fn ($q, $vm) => $q->where('vlan_mode', $vm))
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->odp_id, fn ($q, $id) => $q->where('odp_id', $id))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $onts->through(function ($ont) {
            $ont->replacement_info = $ont->getReplacementInfo();
            return $ont;
        });

        $areas = DataScopeService::getScopedAreas($user, 'network_ont');

        return Inertia::render('Infrastructure/Ont/Index', [
            'onts' => $onts,
            'areas' => $areas,
            'brands' => $brands,
            'stats' => $stats,
            'filters' => $request->only([
                'search',
                'tab',
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
            'pppoe_settings' => self::getPppoeSettings(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ont_id' => 'nullable|string|max:100|unique:onts,ont_id',
            'area_id' => 'nullable|exists:areas,id',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'required|string|max:100',
            'mac_address' => 'nullable|string|max:20',
            'vlan_mode' => 'nullable|string',
            'vlan_id' => 'nullable|string',
            'access_mode' => 'nullable|string',
            'ip_login' => 'nullable|string',
            'login_user' => 'nullable|string',
            'login_password' => 'nullable|string',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
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

        // Mode Hotspot / Member: selaraskan pppoe_user dan hotspot_user agar kredensial selalu sinkron
        if (in_array(strtoupper($validated['access_mode'] ?? ''), ['HOTSPOT', 'HOTSPOT/MEMBER', 'MEMBER'])) {
            $memberUser = ($validated['pppoe_user'] ?? null) ?: ($validated['hotspot_user'] ?? null);
            $memberPass = ($validated['pppoe_password'] ?? null) ?: ($validated['hotspot_password'] ?? null);
            $validated['pppoe_user'] = $memberUser;
            $validated['hotspot_user'] = $memberUser;
            $validated['pppoe_password'] = $memberPass;
            $validated['hotspot_password'] = $memberPass;
        }

        // Auto-reject duplikasi Akun Member / PPPoE
        if (!empty($validated['pppoe_user'])) {
            $duplicate = self::findPppoeDuplicate($validated['pppoe_user']);
            if ($duplicate) {
                $detail = $duplicate->customer ? " (digunakan oleh pelanggan: {$duplicate->customer->name} / {$duplicate->customer->customer_code})" : " (digunakan oleh perangkat ONT SN: {$duplicate->serial_number})";
                return back()->withErrors([
                    'pppoe_user' => "Akun Member / PPPoE '{$validated['pppoe_user']}' sudah terdaftar{$detail}. Sistem otomatis menolak duplikasi akun!"
                ])->withInput();
            }
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
        $ont->load([
            'odp.odc.olt', 
            'customer.package',
            'pgsTicketsReplaced.customer',
            'pgsTicketsReplaced.preparedOnt',
            'pgkTicketsReplaced.customer',
            'pgkTicketsReplaced.preparedOnt',
            'pgsTicketsPrepared.customer',
            'pgsTicketsPrepared.oldOnt',
            'pgkTicketsPrepared.customer',
            'pgkTicketsPrepared.oldOnt',
        ]);
        $ont->replacement_info = $ont->getReplacementInfo();

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
            'serial_number' => 'required|string|max:100',
            'mac_address' => 'nullable|string|max:20',
            'vlan_mode' => 'nullable|string',
            'vlan_id' => 'nullable|string',
            'access_mode' => 'nullable|string',
            'ip_login' => 'nullable|string',
            'login_user' => 'nullable|string',
            'login_password' => 'nullable|string',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
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

        // Mode Hotspot / Member: selaraskan pppoe_user dan hotspot_user agar kredensial selalu sinkron
        if (in_array(strtoupper($validated['access_mode'] ?? ''), ['HOTSPOT', 'HOTSPOT/MEMBER', 'MEMBER'])) {
            $memberUser = ($validated['pppoe_user'] ?? null) ?: ($validated['hotspot_user'] ?? null);
            $memberPass = ($validated['pppoe_password'] ?? null) ?: ($validated['hotspot_password'] ?? null);
            $validated['pppoe_user'] = $memberUser;
            $validated['hotspot_user'] = $memberUser;
            $validated['pppoe_password'] = $memberPass;
            $validated['hotspot_password'] = $memberPass;
        }

        // Auto-reject duplikasi Akun Member / PPPoE
        if (!empty($validated['pppoe_user'])) {
            $duplicate = self::findPppoeDuplicate($validated['pppoe_user'], $ont->id);
            if ($duplicate) {
                $detail = $duplicate->customer ? " (digunakan oleh pelanggan: {$duplicate->customer->name} / {$duplicate->customer->customer_code})" : " (digunakan oleh perangkat ONT SN: {$duplicate->serial_number})";
                return back()->withErrors([
                    'pppoe_user' => "Akun Member / PPPoE '{$validated['pppoe_user']}' sudah terdaftar{$detail}. Sistem otomatis menolak duplikasi akun!"
                ])->withInput();
            }
        }

        $oldOdpId = $ont->odp_id;
        $oldPortNumber = $ont->port_number;
        $newOdpId = $validated['odp_id'] ?? null;
        $newPortNumber = $validated['port_number'] ?? null;

        DB::transaction(function () use ($ont, $validated, $oldOdpId, $oldPortNumber, $newOdpId, $newPortNumber) {
            $lockedOnt = Ont::where('id', $ont->id)->lockForUpdate()->first();
            if (!$lockedOnt) return;

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

            // Sinkronisasi stok material saat status ONT di gudang berubah rusak/diperbaiki
            $oldStatus = strtolower((string)$lockedOnt->status);
            $newStatus = strtolower((string)($validated['status'] ?? $lockedOnt->status));
            $isOldRusak = in_array($oldStatus, ['rusak', 'damaged']);
            $isNewRusak = in_array($newStatus, ['rusak', 'damaged']);
            $isUninstalled = empty($lockedOnt->customer_id) && empty($validated['customer_id']);

            if ($isUninstalled && $lockedOnt->area_id && $isOldRusak !== $isNewRusak) {
                $brandName = $validated['brand'] ?? $lockedOnt->brand;
                $mat = \App\Services\MaterialService::resolveOntMaterial($lockedOnt);
                if (!$mat && $brandName) {
                    $mat = \App\Models\Material::where(function ($mq) use ($brandName) {
                        $mq->where('name', $brandName)
                           ->orWhere('name', 'like', "%{$brandName}%");
                    })->where('category', 'like', '%ont%')->first()
                    ?: \App\Models\Material::where('name', $brandName)->first();
                }

                if ($mat) {
                    $targetUnit = $mat->unit ?: 'pcs';
                    $ppu = \App\Services\MaterialService::calculateUnitPrice($mat, $targetUnit);

                    if ($isNewRusak) {
                        // Berubah dari bagus ke rusak di gudang -> kurangi stok layak pakai area (tanpa mengotori Gudang Pusat Order Toko)
                        $stockRes = \App\Services\MaterialService::deductOperationalStock($mat, 1, $lockedOnt->area_id, $targetUnit);

                        $tx = \App\Models\MaterialTransaction::create([
                            'transaction_number' => \App\Services\MaterialService::generateTransactionNumber('OUT-RUSAK'),
                            'type' => 'out',
                            'status' => 'approved',
                            'date' => now(),
                            'technician_name' => auth()->user() ? auth()->user()->name : 'System',
                            'purpose' => 'ONT Rusak di Gudang',
                            'user_id' => auth()->id(),
                            'area_id' => $lockedOnt->area_id,
                            'notes' => "Unit ONT {$brandName} (SN: {$lockedOnt->serial_number}) diubah status menjadi Rusak",
                        ]);

                        \App\Models\MaterialTransactionItem::create([
                            'material_transaction_id' => $tx->id,
                            'material_id' => $mat->id,
                            'quantity' => 1,
                            'unit' => $targetUnit,
                            'price_per_unit' => $ppu,
                            'total_price' => $ppu,
                            'stock_before' => $stockRes['stock_before'],
                            'stock_after' => $stockRes['stock_after'],
                            'condition' => 'Rusak',
                        ]);
                    } else {
                        // Berubah dari rusak ke bagus (diperbaiki) -> kembalikan ke stok layak pakai area
                        // Cegah penambahan stok ganda jika ONT ini sudah di-restock ke gudang sebelumnya
                        $alreadyStocked = false;

                        if ($lockedOnt->material_transaction_item_id) {
                            $trxItem = \App\Models\MaterialTransactionItem::find($lockedOnt->material_transaction_item_id);
                            if ($trxItem && $trxItem->condition === 'Layak Pakai' && $trxItem->transaction?->handover_status === 'received') {
                                $alreadyStocked = true;
                            }
                        }

                        if (!$alreadyStocked && $lockedOnt->serial_number) {
                            $latestTx = \App\Models\MaterialTransaction::where(function ($tq) use ($lockedOnt) {
                                    $tq->where('old_ont_id', $lockedOnt->id)
                                       ->orWhere('notes', 'like', "%{$lockedOnt->serial_number}%")
                                       ->orWhere('purpose', 'like', "%{$lockedOnt->serial_number}%");
                                })
                                ->whereIn('type', ['in', 'out'])
                                ->latest('id')
                                ->first();

                            if ($latestTx && $latestTx->type === 'in' && in_array($latestTx->handover_status, ['received', 'none'], true)) {
                                $alreadyStocked = true;
                            }
                        }

                        if ($alreadyStocked) {
                            \Illuminate\Support\Facades\Log::info("[OntController] ONT SN: {$lockedOnt->serial_number} sudah memiliki stok aktif di gudang. Melewati penambahan stok ganda.");
                        } else {
                            $stockRes = \App\Services\MaterialService::restoreOperationalStock($mat, 1, $lockedOnt->area_id, $targetUnit);

                            $tx = \App\Models\MaterialTransaction::create([
                                'transaction_number' => \App\Services\MaterialService::generateTransactionNumber('IN-REPAIR'),
                                'type' => 'in',
                                'status' => 'approved',
                                'date' => now(),
                                'technician_name' => auth()->user() ? auth()->user()->name : 'System',
                                'purpose' => 'ONT Selesai Diperbaiki / Layak Pakai',
                                'user_id' => auth()->id(),
                                'area_id' => $lockedOnt->area_id,
                                'old_ont_id' => $lockedOnt->id,
                                'notes' => "Unit ONT {$brandName} (SN: {$lockedOnt->serial_number}) kembali Layak Pakai",
                            ]);

                            \App\Models\MaterialTransactionItem::create([
                                'material_transaction_id' => $tx->id,
                                'material_id' => $mat->id,
                                'quantity' => 1,
                                'unit' => $targetUnit,
                                'price_per_unit' => $ppu,
                                'total_price' => $ppu,
                                'stock_before' => $stockRes['stock_before'],
                                'stock_after' => $stockRes['stock_after'],
                                'condition' => 'Layak Pakai',
                            ]);
                        }
                    }
                }
            }

            $oldPppoeUser = $lockedOnt->pppoe_user;
            $lockedOnt->update($validated);

            if ($lockedOnt->customer_id) {
                $customer = \App\Models\Customer::find($lockedOnt->customer_id);
                if ($customer) {
                    $custSync = [];
                    if (array_key_exists('wifi_ssid', $validated)) $custSync['wifi_ssid'] = $validated['wifi_ssid'];
                    if (array_key_exists('wifi_password', $validated)) $custSync['wifi_password'] = $validated['wifi_password'];
                    if (array_key_exists('pppoe_user', $validated)) $custSync['pppoe_username'] = $validated['pppoe_user'];
                    if (array_key_exists('pppoe_password', $validated)) $custSync['pppoe_password'] = $validated['pppoe_password'];
                    if (!empty($custSync)) {
                        $customer->update($custSync);
                    }

                    // Sinkronisasi server RADIUS MikroTik jika akun dial-up PPPoE diperbarui di Data ONT
                    if (array_key_exists('pppoe_user', $validated) || array_key_exists('pppoe_password', $validated)) {
                        $newPppoeUser = $lockedOnt->pppoe_user;
                        try {
                            app(\App\Services\RadiusService::class)->syncCustomer($customer, array_filter([$oldPppoeUser]));
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('[RADIUS] ONT update PPPoE sync error: ' . $e->getMessage());
                        }

                        if ($oldPppoeUser !== $newPppoeUser) {
                            \App\Models\AuditLog::createLog(
                                'Update PPPoE Data ONT',
                                $lockedOnt,
                                $oldPppoeUser,
                                $newPppoeUser,
                                'Akun Dial-up PPPoE diperbarui melalui Data ONT oleh ' . (auth()->user()?->name ?? 'Admin')
                            );
                        }
                    }
                }
            }

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
        });

        return redirect()->route('onts.index')
            ->with('success', 'Data ONT berhasil diperbarui.');
    }

    public function destroy(Ont $ont): RedirectResponse
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('network_ont_delete') && !auth()->user()->can('network_ont_delete_all')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data ONT.');
        }

        DB::transaction(function () use ($ont) {
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
        });

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

        $onts = $query
            ->when($request->tab, function ($q, $tab) {
                if ($tab === 'ont_diganti') {
                    $q->diganti();
                } elseif ($tab === 'ont_pengganti') {
                    $q->pengganti();
                }
            })->get();
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
                $ont->load([
            'odp.odc.olt', 
            'customer.package',
            'pgsTicketsReplaced.customer',
            'pgsTicketsReplaced.preparedOnt',
            'pgkTicketsReplaced.customer',
            'pgkTicketsReplaced.preparedOnt',
            'pgsTicketsPrepared.customer',
            'pgsTicketsPrepared.oldOnt',
            'pgkTicketsPrepared.customer',
            'pgkTicketsPrepared.oldOnt',
        ]);
        $ont->replacement_info = $ont->getReplacementInfo();
            }
        }

        return Inertia::render('Infrastructure/Ont/FindOnu', [
            'searchQuery' => $search,
            'ont' => $ont,
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // PPPoE Generator & Duplicate Auto-Reject Methods
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Get configured PPPoE generator settings
     */
    public static function getPppoeSettings(): array
    {
        return [
            'pppoe_prefix' => (string) Setting::get('pppoe_prefix', 'VGC'),
            'pppoe_format' => (string) Setting::get('pppoe_format', 'sequential'),
            'pppoe_length' => (int) Setting::get('pppoe_length', 4),
            'pppoe_suffix' => (string) Setting::get('pppoe_suffix', '@vnet.id'),
            'pppoe_case' => (string) Setting::get('pppoe_case', 'standard'),
            'pppoe_password_type' => (string) Setting::get('pppoe_password_type', 'random_numeric'),
            'pppoe_password_length' => (int) Setting::get('pppoe_password_length', 6),
            'pppoe_default_password' => (string) Setting::get('pppoe_default_password', '123456'),
        ];
    }

    /**
     * Update PPPoE generator settings
     */
    public function updatePppoeSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pppoe_prefix' => 'nullable|string|max:50',
            'pppoe_format' => 'required|in:sequential,random_numeric,random_alphanumeric',
            'pppoe_length' => 'required|integer|min:2|max:12',
            'pppoe_suffix' => 'nullable|string|max:50',
            'pppoe_case' => 'required|in:standard,upper,lower',
            'pppoe_password_type' => 'required|in:random_numeric,random_alphanumeric,static',
            'pppoe_password_length' => 'required|integer|min:4|max:32',
            'pppoe_default_password' => 'nullable|string|max:50',
        ]);

        foreach ($validated as $key => $val) {
            Setting::set($key, (string) ($val ?? ''));
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan format generate PPPoE berhasil disimpan.',
            'settings' => self::getPppoeSettings(),
        ]);
    }

    /**
     * Check if a PPPoE username already exists in another ONT (auto-reject duplicate check)
     */
    public static function findPppoeDuplicate(string $username, ?int $ignoreOntId = null): ?Ont
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        return Ont::query()
            ->where(function ($q) use ($username) {
                $q->where('pppoe_user', $username)
                  ->orWhere('hotspot_user', $username);
            })
            ->when($ignoreOntId, fn($q) => $q->where('id', '!=', $ignoreOntId))
            ->with(['customer:id,customer_code,name,phone,address', 'odp:id,name'])
            ->first();
    }

    /**
     * Endpoint to check PPPoE username availability in real-time
     */
    public function checkPppoeUser(Request $request): JsonResponse
    {
        $username = trim((string) $request->input('user', ''));
        $ignoreOntId = $request->filled('ignore_ont_id') ? (int) $request->input('ignore_ont_id') : null;

        if ($username === '') {
            return response()->json([
                'available' => true,
                'message' => '',
            ]);
        }

        $duplicate = self::findPppoeDuplicate($username, $ignoreOntId);

        if ($duplicate) {
            $customerInfo = null;
            if ($duplicate->customer) {
                $customerInfo = [
                    'id' => $duplicate->customer->id,
                    'name' => $duplicate->customer->name,
                    'customer_code' => $duplicate->customer->customer_code,
                    'phone' => $duplicate->customer->phone,
                ];
            }

            $detailDesc = $customerInfo 
                ? "Pelanggan {$customerInfo['name']} ({$customerInfo['customer_code']})" 
                : "ONT SN: {$duplicate->serial_number}";

            return response()->json([
                'available' => false,
                'message' => "Akun Member / PPPoE '{$username}' sudah digunakan oleh {$detailDesc}! Sistem otomatis menolak duplikasi.",
                'conflict' => [
                    'ont_id' => $duplicate->id,
                    'serial_number' => $duplicate->serial_number,
                    'status' => $duplicate->status,
                    'customer' => $customerInfo,
                ],
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Akun PPPoE / Member tersedia & bebas bentrok.',
        ]);
    }

    /**
     * Endpoint to generate a 100% collision-free unique PPPoE username and password
     */
    public function generatePppoe(Request $request): JsonResponse
    {
        $settings = self::getPppoeSettings();
        
        $prefix = $request->input('prefix', $settings['pppoe_prefix'] ?? 'VGC');
        $suffix = $request->input('suffix', $settings['pppoe_suffix'] ?? '@vnet.id');
        $format = $request->input('format', $settings['pppoe_format'] ?? 'sequential');
        $length = (int) $request->input('length', $settings['pppoe_length'] ?? 4);
        $case = $request->input('case', $settings['pppoe_case'] ?? 'standard');

        $pwType = $request->input('password_type', $settings['pppoe_password_type'] ?? 'random_numeric');
        $pwLength = (int) $request->input('password_length', $settings['pppoe_password_length'] ?? 6);
        $staticPw = $request->input('default_password', $settings['pppoe_default_password'] ?? '123456');

        $generatedUser = self::createUniqueUsername($prefix, $suffix, $format, $length, $case);
        $generatedPassword = self::createPassword($pwType, $pwLength, $staticPw);

        return response()->json([
            'success' => true,
            'pppoe_user' => $generatedUser,
            'pppoe_password' => $generatedPassword,
            'user' => $generatedUser,
            'password' => $generatedPassword,
            'prefix' => $prefix,
            'format' => $format,
        ]);
    }

    /**
     * Generate unique username guaranteed to be collision-free
     */
    public static function createUniqueUsername(string $prefix, string $suffix, string $format, int $length, string $case): string
    {
        $prefix = trim($prefix);
        $suffix = trim($suffix);
        $length = max(2, min(12, $length));

        if ($format === 'sequential') {
            $users = Ont::query()
                ->whereNotNull('pppoe_user')
                ->where('pppoe_user', '!=', '')
                ->when($prefix !== '', fn($q) => $q->where('pppoe_user', 'like', $prefix . '%'))
                ->when($suffix !== '', fn($q) => $q->where('pppoe_user', 'like', '%' . $suffix))
                ->pluck('pppoe_user');

            $maxSeq = 0;
            $quotedPrefix = preg_quote($prefix, '/');
            $quotedSuffix = preg_quote($suffix, '/');
            $regex = '/^' . $quotedPrefix . '(\d+)' . $quotedSuffix . '$/i';

            foreach ($users as $u) {
                if (preg_match($regex, trim($u), $m)) {
                    $num = (int) $m[1];
                    if ($num > $maxSeq) {
                        $maxSeq = $num;
                    }
                }
            }

            $candidateSeq = $maxSeq + 1;
            $attempts = 0;

            do {
                $numStr = str_pad((string)$candidateSeq, $length, '0', STR_PAD_LEFT);
                $candidateUser = self::applyCasing($prefix . $numStr . $suffix, $case, $prefix, $numStr, $suffix);

                if (self::findPppoeDuplicate($candidateUser) === null) {
                    return $candidateUser;
                }
                $candidateSeq++;
                $attempts++;
            } while ($attempts < 3000);

            return $candidateUser;
        }

        if ($format === 'random_numeric') {
            $attempts = 0;
            $min = (int) pow(10, max(1, $length - 1));
            $max = (int) pow(10, min(9, $length)) - 1;

            do {
                $numStr = (string) random_int($min, $max);
                $candidateUser = self::applyCasing($prefix . $numStr . $suffix, $case, $prefix, $numStr, $suffix);

                if (self::findPppoeDuplicate($candidateUser) === null) {
                    return $candidateUser;
                }
                $attempts++;
            } while ($attempts < 150);

            return $candidateUser;
        }

        // random_alphanumeric
        $pool = '23456789abcdefghjkmnpqrstuvwxyz';
        $poolLen = strlen($pool);
        $attempts = 0;

        do {
            $randStr = '';
            for ($i = 0; $i < $length; $i++) {
                $randStr .= $pool[random_int(0, $poolLen - 1)];
            }
            $candidateUser = self::applyCasing($prefix . $randStr . $suffix, $case, $prefix, $randStr, $suffix);

            if (self::findPppoeDuplicate($candidateUser) === null) {
                return $candidateUser;
            }
            $attempts++;
        } while ($attempts < 150);

        return $candidateUser;
    }

    /**
     * Apply casing format
     */
    public static function applyCasing(string $user, string $case, string $prefix, string $middle, string $suffix): string
    {
        if ($case === 'upper') {
            return strtoupper($user);
        }
        if ($case === 'lower') {
            return strtolower($user);
        }
        // standard: preserve prefix & suffix as configured, middle as is
        return $prefix . $middle . $suffix;
    }

    /**
     * Generate password according to type & length
     */
    public static function createPassword(string $type, int $length, string $staticPw): string
    {
        $length = max(4, min(32, $length));

        if ($type === 'static') {
            return $staticPw ?: '123456';
        }

        if ($type === 'random_alphanumeric') {
            $pool = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
            $poolLen = strlen($pool);
            $pw = '';
            for ($i = 0; $i < $length; $i++) {
                $pw .= $pool[random_int(0, $poolLen - 1)];
            }
            return $pw;
        }

        // random_numeric
        $pw = '';
        for ($i = 0; $i < $length; $i++) {
            $pw .= (string) random_int(0, 9);
        }
        return $pw;
    }
}
