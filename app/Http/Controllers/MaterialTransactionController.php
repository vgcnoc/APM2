<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialTransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('material_transactions_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        $query = MaterialTransaction::with(['user', 'items.material', 'areaModel']);
        $query = \App\Services\DataScopeService::applyMaterialTransactionScope($query, $user);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('transaction_number', 'like', '%' . $request->search . '%')
                  ->orWhere('technician_name', 'like', '%' . $request->search . '%')
                  ->orWhere('purpose', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->technician) {
            $query->where('technician_name', $request->technician);
        }

        if ($request->area_id) {
            $query->where('area_id', $request->area_id);
        }

        $transactions = $query->latest()->paginate(15)->withQueryString();

        // Calculations for cards
        $baseQuery = \App\Services\DataScopeService::applyMaterialTransactionScope(MaterialTransaction::query(), $user);

        if ($request->start_date) {
            $baseQuery->whereDate('date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $baseQuery->whereDate('date', '<=', $request->end_date);
        }
        if ($request->area_id) {
            $baseQuery->where('area_id', $request->area_id);
        }

        $totalTransactions = (clone $baseQuery)->count();
        $totalCost = (clone $baseQuery)
            ->where('transaction_number', 'not like', '%MIG%')
            ->where('purpose', 'not like', '%migrasi%')
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', 'not like', '%migrasi%');
            })
            ->where(function ($q) {
                $q->whereNull('source_type')->orWhere('source_type', '!=', 'migrasi');
            })
            ->sum('total_cost');
        
        // Total Items
        $totalItems = MaterialTransactionItem::whereHas('transaction', function($q) use ($request, $user) {
            \App\Services\DataScopeService::applyMaterialTransactionScope($q, $user);
            if ($request->start_date) {
                $q->whereDate('date', '>=', $request->start_date);
            }
            if ($request->end_date) {
                $q->whereDate('date', '<=', $request->end_date);
            }
            if ($request->area_id) {
                $q->where('area_id', $request->area_id);
            }
        })->sum('quantity');

        // Get unique technicians for filter
        $techQuery = MaterialTransaction::select('technician_name')->distinct()->whereNotNull('technician_name');
        if (!$isGlobal && !empty($accessibleAreas)) {
            $areaNames = \App\Models\Area::whereIn('id', $accessibleAreas)->pluck('name')->toArray();
            $techQuery->where(function($q) use ($accessibleAreas, $areaNames) {
                $q->whereIn('area_id', $accessibleAreas)
                  ->orWhereIn('area', $areaNames);
            });
        }
        $technicians = $techQuery->pluck('technician_name');
        
        $areaQuery = \App\Models\Area::orderBy('name');
        if (!$isGlobal && !empty($accessibleAreas)) {
            $areaQuery->whereIn('id', $accessibleAreas);
        }
        $areas = $areaQuery->get();
        $materials = \App\Models\Material::with('stocks')->orderBy('name')->get();

        return Inertia::render('MaterialTransactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'start_date', 'end_date', 'technician', 'area_id']),
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_cost' => $totalCost,
                'total_items' => $totalItems,
            ],
            'technicians' => $technicians,
            'areas' => $areas,
            'materials' => $materials,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && $user->role !== 'super admin' && !$user->can('material_transactions_create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat transaksi/surat jalan material.');
        }
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('material_transactions_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        $materials = Material::with('stocks')->get();
        $areaQuery = \App\Models\Area::orderBy('name');
        if (!$isGlobal && !empty($accessibleAreas)) {
            $areaQuery->whereIn('id', $accessibleAreas);
        }
        $areas = $areaQuery->get();
        
        return Inertia::render('MaterialTransactions/Create', [
            'materials' => $materials,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && $user->role !== 'super admin' && !$user->can('material_transactions_create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat transaksi/surat jalan material.');
        }
        $request->validate([
            'date' => 'required|date',
            'technician_name' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
        ]);

        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('material_transactions_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas) && !in_array($request->area_id, $accessibleAreas)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk area ini.');
        }

        try {
            DB::transaction(function () use ($request) {
                $totalCost = 0;

                // Create Transaction
                $transaction = MaterialTransaction::create([
                    'transaction_number' => \App\Services\MaterialService::generateTransactionNumber('IN-AREA'),
                    'type' => 'in',
                    'status' => 'approved',
                    'date' => $request->date,
                    'technician_name' => $request->technician_name,
                    'purpose' => $request->purpose,
                    'area_id' => $request->area_id,
                    'notes' => $request->notes,
                    'user_id' => auth()->id(),
                    'total_cost' => 0,
                ]);

                foreach ($request->items as $itemData) {
                    $material = Material::lockForUpdate()->findOrFail($itemData['material_id']);
                    $materialStock = \App\Services\MaterialService::getOrCreateLockedAreaStock($material->id, (int) $request->area_id);

                    $itemUnit = $itemData['unit'] ?? $material->unit;
                    $deduction = \App\Services\MaterialService::convertToBaseQuantity($material, (float) $itemData['quantity'], $itemUnit);

                    // Ensure sufficient stock in global inventory AFTER converting to base unit
                    if ((float) $material->stock < $deduction) {
                        throw new \Exception("Stok {$material->name} di Gudang Utama tidak mencukupi (Tersisa: {$material->stock} {$material->unit}, dibutuhkan: {$deduction} {$material->unit}).");
                    }

                    $pricePerUnit = \App\Services\MaterialService::calculateUnitPrice($material, $itemUnit, true);
                    $totalPrice = (float) $itemData['quantity'] * $pricePerUnit;
                    $totalCost += $totalPrice;

                    $stockBefore = (float) $materialStock->stock;
                    $stockAfter = $stockBefore + $deduction;

                    $material->stock = max(0.0, (float) $material->stock - $deduction);
                    $material->recalcUnitTotals()->save();

                    $materialStock->stock = $stockAfter;
                    $materialStock->initial_stock = (float) ($materialStock->initial_stock ?? 0) + $deduction;
                    \App\Services\MaterialService::recalcAreaStockUnits($materialStock, $material)->save();

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $transaction->id,
                        'material_id' => $material->id,
                        'material_name' => $material->name,
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemUnit,
                        'price_per_unit' => $pricePerUnit,
                        'total_price' => $totalPrice,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'condition' => 'Layak Pakai'
                    ]);
                }

                $transaction->update(['total_cost' => $totalCost]);
            });
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('material-transactions.index')
            ->with('success', 'Order/Pengambilan Barang berhasil dicatat dan stok telah dikurangi.');
    }

    public function approveReturn(MaterialTransaction $material_transaction)
    {
        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('material_transactions_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas) && $material_transaction->area_id && !in_array((int) $material_transaction->area_id, array_map('intval', $accessibleAreas), true)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menyetujui pengembalian area ini.');
        }

        if ($material_transaction->type !== 'out' || $material_transaction->status !== 'pending' || !str_contains($material_transaction->purpose, 'Pengembalian Sisa')) {
            return redirect()->back()->with('error', 'Transaksi tidak valid untuk disetujui.');
        }

        try {
            DB::transaction(function () use ($material_transaction) {
                $lockedTrx = MaterialTransaction::whereKey($material_transaction->id)->lockForUpdate()->with('items')->firstOrFail();
                if ($lockedTrx->status !== 'pending') {
                    throw new \Exception('Transaksi pengembalian ini sudah diproses sebelumnya.');
                }

                $lockedTrx->update(['status' => 'approved']);

                // Kembalikan dari Stok Area ke Gudang Pusat
                foreach ($lockedTrx->items as $item) {
                    $material = \App\Models\Material::whereKey($item->material_id)->lockForUpdate()->first();
                    $materialStock = $lockedTrx->area_id
                        ? \App\Models\MaterialStock::where('material_id', $item->material_id)
                            ->where('area_id', $lockedTrx->area_id)
                            ->lockForUpdate()
                            ->first()
                        : null;

                    if ($material) {
                        $baseQty = \App\Services\MaterialService::convertToBaseQuantity($material, (float) $item->quantity, $item->unit);
                        $stockBefore = $materialStock ? (float) $materialStock->stock : (float) $material->stock;
                        $stockAfter = $materialStock ? max(0.0, $stockBefore - $baseQty) : ($stockBefore + $baseQty);

                        $item->update([
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                        ]);

                        if ($materialStock) {
                            $materialStock->stock = $stockAfter;
                            \App\Services\MaterialService::recalcAreaStockUnits($materialStock, $material)->save();
                        }

                        $material->stock = max(0.0, (float) $material->stock + $baseQty);
                        $material->recalcUnitTotals()->save();
                    }
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Serah terima pengembalian material berhasil disetujui! Stok Gudang bertambah dan Stok Area berkurang.');
    }

    public function rejectReturn(MaterialTransaction $material_transaction)
    {
        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('material_transactions_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas) && $material_transaction->area_id && !in_array((int) $material_transaction->area_id, array_map('intval', $accessibleAreas), true)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menolak pengembalian area ini.');
        }

        if ($material_transaction->type !== 'out' || $material_transaction->status !== 'pending' || !str_contains($material_transaction->purpose, 'Pengembalian Sisa')) {
            return redirect()->back()->with('error', 'Transaksi tidak valid untuk ditolak.');
        }

        DB::transaction(function () use ($material_transaction) {
            $lockedTrx = MaterialTransaction::whereKey($material_transaction->id)->lockForUpdate()->firstOrFail();
            if ($lockedTrx->status === 'pending') {
                $lockedTrx->update(['status' => 'rejected']);
            }
        });

        return redirect()->back()->with('success', 'Pengembalian material telah ditolak.');
    }


    public function edit(MaterialTransaction $materialTransaction)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && $user->role !== 'super admin' && !$user->can('material_transactions_edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah transaksi/surat jalan material.');
        }

        $materialTransaction->load('items.material');
        return \Inertia\Inertia::render('MaterialTransactions/Edit', [
            'transaction' => $materialTransaction,
            'materials' => \App\Models\Material::orderBy('name')->get(),
            'areas' => \App\Models\Area::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, MaterialTransaction $materialTransaction)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && $user->role !== 'super admin' && !$user->can('material_transactions_edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah transaksi/surat jalan material.');
        }
        $request->validate([
            'date' => 'required|date',
            'technician_name' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
        ]);

        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('material_transactions_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas)) {
            if (!in_array($request->area_id, $accessibleAreas)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk area ini.');
            }
            if ($materialTransaction->area_id && !in_array($materialTransaction->area_id, $accessibleAreas)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk mengubah transaksi area ini.');
            }
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $materialTransaction) {
                $lockedTrx = MaterialTransaction::whereKey($materialTransaction->id)->lockForUpdate()->with('items.material')->firstOrFail();

                // Revert old stocks
                foreach ($lockedTrx->items as $item) {
                    $material = Material::whereKey($item->material_id)->lockForUpdate()->first();
                    if ($material) {
                        $addition = \App\Services\MaterialService::convertToBaseQuantity($material, (float) $item->quantity, $item->unit);
                        $material->stock = max(0.0, (float) $material->stock + $addition);
                        $material->recalcUnitTotals()->save();

                        if ($lockedTrx->area_id) {
                            $materialStock = \App\Services\MaterialService::getOrCreateLockedAreaStock($material->id, (int) $lockedTrx->area_id);
                            $materialStock->stock = max(0.0, (float) $materialStock->stock - $addition);
                            $materialStock->initial_stock = max(0.0, (float) $materialStock->initial_stock - $addition);
                            \App\Services\MaterialService::recalcAreaStockUnits($materialStock, $material)->save();
                        }
                    }
                }

                // Delete old items
                $lockedTrx->items()->delete();

                $totalCost = 0;
                // Create New Items and deduct stock
                foreach ($request->items as $itemData) {
                    $material = \App\Models\Material::lockForUpdate()->findOrFail($itemData['material_id']);
                    $materialStock = \App\Services\MaterialService::getOrCreateLockedAreaStock($material->id, (int) $request->area_id);

                    $itemUnit = $itemData['unit'] ?? $material->unit;
                    $deduction = \App\Services\MaterialService::convertToBaseQuantity($material, (float) $itemData['quantity'], $itemUnit);

                    if ((float) $material->stock < $deduction) {
                        throw new \Exception("Stok {$material->name} di Gudang Utama tidak mencukupi (Tersisa: {$material->stock} {$material->unit}, dibutuhkan: {$deduction} {$material->unit}).");
                    }

                    $pricePerUnit = \App\Services\MaterialService::calculateUnitPrice($material, $itemUnit, true);
                    $totalPrice = (float) $itemData['quantity'] * $pricePerUnit;
                    $totalCost += $totalPrice;

                    $stockBefore = (float) $materialStock->stock;
                    $stockAfter = $stockBefore + $deduction;

                    $material->stock = max(0.0, (float) $material->stock - $deduction);
                    $material->recalcUnitTotals()->save();

                    $materialStock->stock = $stockAfter;
                    $materialStock->initial_stock = (float) ($materialStock->initial_stock ?? 0) + $deduction;
                    \App\Services\MaterialService::recalcAreaStockUnits($materialStock, $material)->save();

                    \App\Models\MaterialTransactionItem::create([
                        'material_transaction_id' => $lockedTrx->id,
                        'material_id' => $material->id,
                        'material_name' => $material->name,
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemUnit,
                        'price_per_unit' => $pricePerUnit,
                        'total_price' => $totalPrice,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'condition' => 'Layak Pakai',
                    ]);
                }

                $lockedTrx->update([
                    'date' => $request->date,
                    'technician_name' => $request->technician_name,
                    'purpose' => $request->purpose,
                    'area_id' => $request->area_id,
                    'notes' => $request->notes,
                    'total_cost' => $totalCost
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('material-transactions.index')
            ->with('success', 'Order/Pengambilan Barang berhasil diperbarui.');
    }
    public function show(MaterialTransaction $materialTransaction)
    {
        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('material_transactions_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas) && $materialTransaction->area_id && !in_array($materialTransaction->area_id, $accessibleAreas)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat surat jalan di area ini.');
        }

        $materialTransaction->load(['items.material', 'user']);
        
        return Inertia::render('MaterialTransactions/Show', [
            'transaction' => $materialTransaction
        ]);
    }

    public function registerOnt(Request $request, MaterialTransactionItem $item)
    {
        $item->load(['transaction', 'material']);
        
        $sns = [];
        if ($request->filled('sn_list')) {
            $sns = array_filter(array_map('trim', preg_split('/[\n,]+/', $request->sn_list)));
            if (count($sns) > $item->quantity) {
                return back()->withErrors(['sn_list' => 'Jumlah Serial Number (' . count($sns) . ') melebihi jumlah kuantitas barang (' . $item->quantity . ').']);
            }
        } else {
            // Auto-generate SNs
            for ($i = 0; $i < $item->quantity; $i++) {
                $sns[] = 'AUTO-' . strtoupper(Str::random(6)) . '-' . time() . '-' . $i;
            }
        }

        foreach ($sns as $sn) {
            $areaId = $item->transaction->area_id;

            $existing = \App\Models\Ont::where('serial_number', $sn)->first();
            if (!$existing) {
                \App\Models\Ont::create([
                    'serial_number' => $sn,
                    'brand' => $item->material->name,
                    'status' => 'Belum Set/Baru Input',
                    'material_transaction_item_id' => $item->id,
                    'area_id' => $areaId,
                    'description' => "Pengambilan dari Gudang oleh: " . $item->transaction->technician_name . " (Tujuan: " . $item->transaction->purpose . ") pada " . $item->transaction->date,
                ]);
            } else {
                $existing->update([
                    'status' => 'Belum Set/Baru Input',
                    'material_transaction_item_id' => $item->id,
                    'area_id' => $areaId,
                    'description' => "Pengambilan Ulang dari Gudang oleh: " . $item->transaction->technician_name . " (Tujuan: " . $item->transaction->purpose . ") pada " . $item->transaction->date,
                ]);
            }
        }

        $item->update(['is_registered_to_ont' => true]);

        return back()->with('success', count($sns) . ' perangkat berhasil didaftarkan ke Menu ONT.');
    }

    public function resetOnt(MaterialTransactionItem $item)
    {
        $item->update(['is_registered_to_ont' => false]);
        return back()->with('success', 'Status pendaftaran ONT berhasil direset. Silakan daftarkan ulang jika diperlukan.');
    }

    public function destroy(MaterialTransaction $materialTransaction)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && $user->role !== 'super admin' && !$user->can('material_transactions_delete')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus transaksi/surat jalan material.');
        }

        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('material_transactions_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas) && $materialTransaction->area_id && !in_array($materialTransaction->area_id, $accessibleAreas)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus transaksi area ini.');
        }

        try {
            DB::transaction(function () use ($materialTransaction) {
                // Delete transaction (model deleting event automatically restores stock and cascades customer if linked)
                $materialTransaction->delete();
            });

            return redirect()->route('material-transactions.index')
                ->with('success', 'Riwayat Order berhasil dihapus, stok telah dikembalikan ke Gudang, dan data terkait telah disinkronkan.');
        } catch (\Exception $e) {
            return redirect()->route('material-transactions.index')
                ->with('error', 'Gagal menghapus riwayat order: ' . $e->getMessage());
        }
    }

    public function bulkDestroy(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && $user->role !== 'super admin' && !$user->can('material_transactions_delete')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus transaksi/surat jalan material.');
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:material_transactions,id',
        ]);

        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('material_transactions_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        try {
            $deletedCount = 0;
            DB::transaction(function () use ($request, $isGlobal, $accessibleAreas, &$deletedCount) {
                $query = MaterialTransaction::whereIn('id', $request->ids)->lockForUpdate();
                if (!$isGlobal) {
                    if (empty($accessibleAreas)) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('area_id', $accessibleAreas);
                    }
                }
                $transactions = $query->get();

                foreach ($transactions as $transaction) {
                    // Delete transaction (model deleting event automatically restores stock and cascades customer if linked)
                    $transaction->delete();
                    $deletedCount++;
                }
            });

            return redirect()->back()->with('success', 'Berhasil menghapus ' . $deletedCount . ' transaksi, stok telah dikembalikan ke Gudang, dan data terkait telah disinkronkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

