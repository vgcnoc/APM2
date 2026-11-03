<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StockInOutController extends Controller
{
    public function index(Request $request)
    {
        // Get all transactions EXCEPT purchasing ones (managed in Order Toko)
        // Note: some legacy transactions might have different purposes. 
        // We'll exclude 'Pembelian Toko / Supplier' to isolate mutasi internal.
        $query = MaterialTransaction::where('purpose', '!=', 'Pembelian Toko / Supplier')
                            ->with('items.material');

        // 1. Pencarian Teks Fleksibel (No Ref, Teknisi/Penerima, Area, Keperluan, Catatan, Nama Material)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('technician_name', 'like', "%{$search}%")
                  ->orWhere('area', 'like', "%{$search}%")
                  ->orWhere('transaction_number', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('items.material', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('category', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filter Tipe Transaksi (in / out)
        if ($request->filled('type') && in_array($request->type, ['in', 'out'])) {
            $query->where('type', $request->type);
        }

        // 3. Filter Keperluan / Purpose
        if ($request->filled('purpose')) {
            $query->where('purpose', $request->purpose);
        }

        // 4. Filter Area / Teknisi
        if ($request->filled('technician_name')) {
            $tech = trim($request->technician_name);
            $query->where(function ($q) use ($tech) {
                $q->where('technician_name', $tech)
                  ->orWhere('area', $tech);
            });
        }

        // 5. Filter Material / Barang
        if ($request->filled('material_id')) {
            $materialId = $request->material_id;
            $query->whereHas('items', function ($q) use ($materialId) {
                $q->where('material_id', $materialId);
            });
        }

        // 6. Filter Rentang Tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // 7. Filter Kondisi Barang (Layak Pakai, Tidak Layak, Rusak)
        if ($request->filled('condition')) {
            $cond = $request->condition;
            $query->whereHas('items', function ($q) use ($cond) {
                $q->where('condition', $cond);
            });
        }
        
        // Hitung ringkasan total mutasi masuk dan keluar sesuai filter
        $summaryQuery = clone $query;
        $inCount = (clone $summaryQuery)->where('type', 'in')->count();
        $outCount = (clone $summaryQuery)->where('type', 'out')->count();

        $transactions = $query->latest('date')->latest('id')->paginate(15)->withQueryString();
        $materials = Material::orderBy('name')->get();
        $purposes = MaterialTransaction::where('purpose', '!=', 'Pembelian Toko / Supplier')
            ->distinct()->pluck('purpose')->filter()->values();
        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Inventory/StockInOut/Index', [
            'transactions' => $transactions,
            'materials' => $materials,
            'purposes' => $purposes,
            'areas' => $areas,
            'filters' => $request->only(['search', 'type', 'purpose', 'technician_name', 'material_id', 'date_from', 'date_to', 'condition']),
            'summary' => [
                'total_count' => $transactions->total(),
                'in_count' => $inCount,
                'out_count' => $outCount,
            ]
        ]);
    }

    protected function resolveTransactionAreaId(?int $explicitAreaId, ?string $technicianName, ?int $materialId = null): ?int
    {
        if ($explicitAreaId) {
            return $explicitAreaId;
        }

        $cleanTech = trim((string) ($technicianName ?? ''));
        if ($cleanTech !== '') {
            $areaId = \App\Models\Area::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($cleanTech)])->value('id');
            if ($areaId) {
                return (int) $areaId;
            }

            $matchedUser = \App\Models\User::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($cleanTech)])
                ->when(strlen($cleanTech) >= 3, function ($q) use ($cleanTech) {
                    $q->orWhere('name', 'like', $cleanTech . '%');
                })
                ->orderByRaw('CASE WHEN LOWER(TRIM(name)) = ? THEN 0 ELSE 1 END', [strtolower($cleanTech)])
                ->first();

            if ($matchedUser) {
                if (!empty($matchedUser->area_id)) {
                    return (int) $matchedUser->area_id;
                }
                if (method_exists($matchedUser, 'getAccessibleAreaIds')) {
                    $accAreas = $matchedUser->getAccessibleAreaIds();
                    if (count($accAreas) === 1) {
                        return (int) $accAreas[0];
                    }
                }
            }
        }

        $authUser = auth()->user();
        if ($authUser && !empty($authUser->area_id)) {
            $isGlobal = $authUser->hasRole('admin') || $authUser->hasRole('super admin') || in_array($authUser->role, ['admin', 'super admin'], true);
            if (!$isGlobal) {
                return (int) $authUser->area_id;
            }
        }

        if ($materialId) {
            $stockAreaIds = \App\Models\MaterialStock::where('material_id', $materialId)
                ->pluck('area_id')
                ->filter()
                ->unique()
                ->values();
            if ($stockAreaIds->count() === 1) {
                return (int) $stockAreaIds->first();
            }
        }

        return null;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'date' => 'required|date',
            'area_id' => 'nullable|exists:areas,id',
            'technician_name' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.condition' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            // Generate Transaction Number (MUT)
            $count = MaterialTransaction::whereDate('created_at', today())->count() + 1;
            $prefix = $validated['type'] === 'out' ? 'OUT-' : 'RET-'; // Retur (In) or Out
            $transactionNumber = $prefix . now()->format('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $singleMaterialId = count($validated['items']) === 1 ? (int) $validated['items'][0]['material_id'] : null;
            $resolvedAreaId = $this->resolveTransactionAreaId(
                !empty($validated['area_id']) ? (int) $validated['area_id'] : null,
                $validated['technician_name'] ?? null,
                $singleMaterialId
            );
            $resolvedAreaName = $resolvedAreaId
                ? \App\Models\Area::where('id', $resolvedAreaId)->value('name')
                : null;

            $transaction = MaterialTransaction::create([
                'transaction_number' => $transactionNumber,
                'type' => $validated['type'],
                'status' => 'approved',
                'date' => $validated['date'],
                'purpose' => $validated['purpose'],
                'technician_name' => $validated['technician_name'],
                'area_id' => $resolvedAreaId,
                'area' => $resolvedAreaName,
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'total_cost' => 0 
            ]);

            $totalCost = 0;

            foreach ($validated['items'] as $item) {
                $material = Material::findOrFail($item['material_id']);
                $quantity = (float) $item['quantity'];
                $itemCondition = $item['condition'] ?? 'Layak Pakai';
                $targetAreaId = $resolvedAreaId ?: $this->resolveTransactionAreaId(
                    null,
                    $validated['technician_name'] ?? null,
                    $material->id
                );

                if (!$transaction->area_id && $targetAreaId) {
                    $transaction->area_id = $targetAreaId;
                    $transaction->area = \App\Models\Area::where('id', $targetAreaId)->value('name');
                }

                $currentStock = $targetAreaId
                    ? (float) (\App\Models\MaterialStock::where('material_id', $material->id)->where('area_id', $targetAreaId)->value('stock') ?? 0)
                    : (float) $material->stock;
                $stockBefore = $currentStock;
                $stockAfter = $currentStock;

                // Update Stock (Untuk retur 'in', hanya tambah stok aktif jika kondisi Layak Pakai)
                $shouldAdjustStock = ($validated['type'] === 'out') || ($itemCondition === 'Layak Pakai');
                if ($shouldAdjustStock) {
                    if ($validated['type'] === 'out') {
                        $stockRes = \App\Services\MaterialService::deductOperationalStock(
                            $material,
                            $quantity,
                            $targetAreaId,
                            $material->unit
                        );
                    } else {
                        $stockRes = \App\Services\MaterialService::restoreOperationalStock(
                            $material,
                            $quantity,
                            $targetAreaId,
                            $material->unit
                        );
                    }
                    $stockBefore = $stockRes['stock_before'];
                    $stockAfter = $stockRes['stock_after'];
                }

                $itemCost = $quantity * (float) $material->price_per_unit;
                $totalCost += $itemCost;

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $quantity,
                    'unit' => $material->unit,
                    'price_per_unit' => $material->price_per_unit,
                    'total_price' => $itemCost,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'condition' => $itemCondition,
                ]);
            }

            $transaction->total_cost = $totalCost;
            $transaction->save();

            DB::commit();
            return redirect()->back()->with('success', 'Transaksi mutasi berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat mutasi: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $transaction = MaterialTransaction::with('items.material')->findOrFail($id);

        DB::beginTransaction();
        try {
            $singleMatId = $transaction->items->count() === 1 ? (int) $transaction->items->first()?->material_id : null;
            $targetAreaId = $this->resolveTransactionAreaId(
                $transaction->area_id ? (int) $transaction->area_id : null,
                $transaction->technician_name,
                $singleMatId
            );

            // Revert stock secara simetris per area atau gudang utama
            if ($transaction->status === 'approved' && !$transaction->is_migration) {
                foreach ($transaction->items as $item) {
                    $material = $item->material;
                    $qty = (float) $item->quantity;
                    if (!$material || $qty <= 0) {
                        continue;
                    }

                    if ($transaction->type === 'out') {
                        // Reverting an 'out' transaction means adding stock back
                        \App\Services\MaterialService::restoreOperationalStock(
                            $material,
                            $qty,
                            $targetAreaId,
                            $item->unit
                        );
                    } else {
                        // Reverting an 'in' transaction only deducts if it previously added Layak Pakai stock
                        $itemCond = trim((string) ($item->condition ?? 'Layak Pakai'));
                        if ($itemCond === 'Layak Pakai') {
                            \App\Services\MaterialService::deductOperationalStock(
                                $material,
                                $qty,
                                $targetAreaId,
                                $item->unit
                            );
                        }
                    }
                }
            }

            $transaction->skipStockRestoreOnDelete = true;
            $transaction->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Transaksi mutasi berhasil dibatalkan dan stok dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membatalkan mutasi: ' . $e->getMessage());
        }
    }
}
