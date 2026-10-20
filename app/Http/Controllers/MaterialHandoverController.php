<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\Ont;
use App\Services\MaterialHandoverService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaterialHandoverController extends Controller
{
    /**
     * Halaman Utama Serah Terima & Permintaan Material Lapangan.
     */
    public function index(Request $request): Response
    {
        $tab = $request->query('tab', 'returns'); // 'returns' | 'requests' | 'history'
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $sourceType = $request->query('source_type', '');
        $areaId = $request->query('area_id', '');
        $condition = $request->query('condition', '');

        // Summary Counters
        $pendingReturnsCount = MaterialTransaction::where(function ($q) {
                $q->where('request_type', 'return_handover')
                  ->orWhere('transaction_number', 'like', 'RTR-%')
                  ->orWhere('transaction_number', 'like', 'IN-%-RET-%')
                  ->orWhere('purpose', 'like', 'Pengembalian%');
            })
            ->where(function ($q) {
                $q->where('handover_status', 'pending_handover')
                  ->orWhere(function ($sq) {
                      $sq->where('status', 'pending')
                         ->where(function ($ssq) {
                             $ssq->whereNull('handover_status')->orWhere('handover_status', 'none');
                         });
                  });
            })
            ->count();

        $pendingRequestsCount = MaterialTransaction::whereIn('request_type', ['additional_material', 'ont_replacement'])
            ->where('status', 'pending')
            ->count();

        $totalReceivedCount = MaterialTransaction::where('handover_status', 'received')
            ->orWhere(function ($sq) {
                $sq->where('status', 'approved')
                   ->where(function ($ssq) {
                       $ssq->where('request_type', 'return_handover')
                           ->orWhere('transaction_number', 'like', 'RTR-%')
                           ->orWhere('transaction_number', 'like', 'IN-%-RET-%')
                           ->orWhere('purpose', 'like', 'Pengembalian%')
                           ->orWhereIn('request_type', ['additional_material', 'ont_replacement']);
                   });
            })
            ->count();

        $receivedTodayCount = MaterialTransaction::where(function ($q) {
                $q->where('handover_status', 'received')
                  ->orWhere('status', 'approved');
            })
            ->where(function ($q) {
                $q->whereDate('received_at', today())
                  ->orWhere(function ($sq) {
                      $sq->whereNull('received_at')->whereDate('created_at', today());
                  });
            })
            ->count();

        // Base Query
        $query = MaterialTransaction::with([
            'items.material',
            'user',
            'receiver',
            'areaModel',
            'customer',
            'oldOnt',
            'replacementOnt',
        ])->latest('id');

        if ($tab === 'returns') {
            $query->where(function ($q) {
                $q->where('request_type', 'return_handover')
                  ->orWhere('transaction_number', 'like', 'RTR-%')
                  ->orWhere('transaction_number', 'like', 'IN-%-RET-%')
                  ->orWhere('purpose', 'like', 'Pengembalian%');
            });

            if ($status) {
                $query->where('handover_status', $status);
            } else {
                // Default: tampilkan yang pending di atas
                $query->where(function ($q) {
                    $q->whereIn('handover_status', ['pending_handover', 'rejected'])
                      ->orWhere(function ($sq) {
                          $sq->where('status', 'pending')
                             ->where(function ($ssq) {
                                 $ssq->whereNull('handover_status')->orWhere('handover_status', 'none');
                             });
                      });
                });
            }
        } elseif ($tab === 'requests') {
            $query->whereIn('request_type', ['additional_material', 'ont_replacement']);
            if ($status) {
                $query->where('status', $status);
            } else {
                $query->whereIn('status', ['pending', 'rejected']);
            }
        } else { // history
            $query->where(function ($q) {
                $q->where('handover_status', 'received')
                  ->orWhere(function ($sq) {
                      $sq->whereIn('request_type', ['additional_material', 'ont_replacement'])
                         ->where('status', 'approved');
                  })
                  ->orWhere(function ($sq) {
                      $sq->where('status', 'approved')
                         ->where(function ($ssq) {
                             $ssq->where('request_type', 'return_handover')
                                 ->orWhere('transaction_number', 'like', 'RTR-%')
                                 ->orWhere('transaction_number', 'like', 'IN-%-RET-%')
                                 ->orWhere('purpose', 'like', 'Pengembalian%');
                         });
                  });
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('technician_name', 'like', "%{$search}%")
                  ->orWhere('source_reference', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhereHas('items.material', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($sourceType) {
            $query->where('source_type', $sourceType);
        }

        if ($areaId) {
            $query->where('area_id', $areaId);
        }

        if ($condition) {
            $query->whereHas('items', function ($iq) use ($condition) {
                $iq->where('condition', $condition);
            });
        }

        $transactions = $query->paginate(15)->withQueryString();

        // Dropdown Data
        $materials = Material::select('id', 'name', 'unit', 'stock', 'category')->where('is_active', true)->orderBy('name')->get();
        $areas = Area::select('id', 'name')->orderBy('name')->get();
        $availableOnts = Ont::select('id', 'serial_number', 'brand', 'model', 'status', 'area_id')
            ->whereIn('status', ['inventory', 'ready', 'available', 'stock'])
            ->orderBy('serial_number')
            ->get();

        return Inertia::render('Inventory/Handover/Index', [
            'transactions' => $transactions,
            'materials' => $materials,
            'areas' => $areas,
            'availableOnts' => $availableOnts,
            'currentTab' => $tab,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
                'status' => $status,
                'source_type' => $sourceType,
                'area_id' => $areaId,
                'condition' => $condition,
            ],
            'summary' => [
                'pending_returns' => $pendingReturnsCount,
                'pending_requests' => $pendingRequestsCount,
                'received_today' => $receivedTodayCount,
                'total_received' => $totalReceivedCount,
            ],
        ]);
    }

    /**
     * Konfirmasi Penerimaan Serah Terima oleh Karyawan Gudang / Kepala Teknisi.
     */
    public function receive(Request $request, MaterialTransaction $transaction)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:material_transaction_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.condition' => 'required|string|in:Layak Pakai,Tidak Layak,Rusak',
        ]);

        MaterialHandoverService::receiveHandover(
            $transaction,
            $validated['items'],
            auth()->user(),
            $validated['notes'] ?? null
        );

        return back()->with('success', "Serah terima material [{$transaction->transaction_number}] berhasil diverifikasi dan stok masuk ke Inventory Gudang.");
    }

    /**
     * Tolak Serah Terima Pengembalian.
     */
    public function rejectHandover(Request $request, MaterialTransaction $transaction)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        MaterialHandoverService::rejectHandover($transaction, auth()->user(), $validated['reason']);

        return back()->with('error', "Serah terima pengembalian [{$transaction->transaction_number}] ditolak dengan catatan: {$validated['reason']}");
    }

    /**
     * Buat Pengembalian Material Baru secara manual / cepat.
     */
    public function storeReturn(Request $request)
    {
        $validated = $request->validate([
            'source_type' => 'required|string|in:installation,pgs,pgk,pgj,rollout_odc,rollout_odp,manual',
            'source_id' => 'nullable|integer',
            'source_reference' => 'nullable|string|max:100',
            'technician_name' => 'required|string|max:100',
            'area_id' => 'nullable|exists:areas,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
            'items.*.condition' => 'required|string|in:Layak Pakai,Tidak Layak,Rusak',
        ]);

        $trx = MaterialHandoverService::submitReturnHandover(
            $validated['source_type'],
            $validated['source_id'] ?? null,
            $validated['source_reference'] ?? 'Manual',
            $validated['items'],
            $validated['technician_name'],
            $validated['area_id'] ?? null,
            $validated['notes'] ?? null,
            auth()->user()
        );

        return back()->with('success', "Pengembalian material [{$trx->transaction_number}] berhasil diajukan dan menunggu Serah Terima oleh Petugas Gudang / Ka. Teknisi.");
    }

    /**
     * Buat Permintaan Material Baru atau Ganti ONT.
     */
    public function storeRequest(Request $request)
    {
        $validated = $request->validate([
            'source_type' => 'required|string|in:installation,pgs,pgk,pgj,rollout_odc,rollout_odp,manual',
            'source_id' => 'nullable|integer',
            'source_reference' => 'nullable|string|max:100',
            'request_type' => 'required|string|in:additional_material,ont_replacement',
            'area_id' => 'nullable|exists:areas,id',
            'old_ont_id' => 'nullable|exists:onts,id',
            'replacement_ont_id' => 'nullable|exists:onts,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
        ]);

        $items = $validated['items'] ?? [];

        // Jika request pergantian ONT dan ada ONT yang dipilih, tambahkan ke item jika belum ada
        if ($validated['request_type'] === 'ont_replacement' && !empty($validated['replacement_ont_id'])) {
            $repOnt = Ont::find($validated['replacement_ont_id']);
            if ($repOnt && $repOnt->material_id && empty($items)) {
                $items[] = [
                    'material_id' => $repOnt->material_id,
                    'quantity' => 1,
                    'unit' => 'pcs',
                ];
            }
        }

        $trx = MaterialHandoverService::submitRequest(
            $validated['source_type'],
            $validated['source_id'] ?? null,
            $validated['source_reference'] ?? 'Manual Request',
            $validated['request_type'],
            $items,
            $validated['old_ont_id'] ?? null,
            $validated['replacement_ont_id'] ?? null,
            auth()->user(),
            $validated['notes'] ?? null,
            $validated['area_id'] ?? null
        );

        return back()->with('success', "Permintaan [{$trx->transaction_number}] berhasil diajukan dan menunggu persetujuan Kepala Teknisi.");
    }

    /**
     * Setujui Permintaan Material / ONT oleh Kepala Teknisi.
     */
    public function approveRequest(Request $request, MaterialTransaction $transaction)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        MaterialHandoverService::approveRequest($transaction, auth()->user(), $validated['notes'] ?? null);

        return back()->with('success', "Permintaan material [{$transaction->transaction_number}] berhasil disetujui oleh Kepala Teknisi dan stok telah dipotong dari Gudang.");
    }

    /**
     * Tolak Permintaan Material / ONT oleh Kepala Teknisi.
     */
    public function rejectRequest(Request $request, MaterialTransaction $transaction)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        MaterialHandoverService::rejectRequest($transaction, auth()->user(), $validated['reason']);

        return back()->with('error', "Permintaan [{$transaction->transaction_number}] ditolak dengan alasan: {$validated['reason']}");
    }
}
