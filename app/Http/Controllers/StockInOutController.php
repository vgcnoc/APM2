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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'date' => 'required|date',
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

            $matchedArea = \App\Models\Area::where('name', trim($validated['technician_name']))->first();

            $transaction = MaterialTransaction::create([
                'transaction_number' => $transactionNumber,
                'type' => $validated['type'],
                'status' => 'approved',
                'date' => $validated['date'],
                'purpose' => $validated['purpose'],
                'technician_name' => $validated['technician_name'],
                'area_id' => $matchedArea?->id,
                'notes' => $validated['notes'],
                'user_id' => auth()->id(),
                'total_cost' => 0 
            ]);

            foreach ($validated['items'] as $item) {
                $material = Material::findOrFail($item['material_id']);
                $quantity = (float) $item['quantity'];
                $itemCondition = $item['condition'] ?? 'Layak Pakai';

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $quantity,
                    'unit' => $material->unit,
                    'price_per_unit' => $material->price_per_unit,
                    'total_price' => $quantity * $material->price_per_unit,
                    'condition' => $itemCondition,
                ]);

                // Update Stock (Untuk retur 'in', hanya tambah stok aktif jika kondisi Layak Pakai)
                $shouldAdjustStock = ($validated['type'] === 'out') || ($itemCondition === 'Layak Pakai');
                if ($shouldAdjustStock) {
                    if ($matchedArea) {
                        $matStock = \App\Models\MaterialStock::firstOrCreate(
                            ['material_id' => $material->id, 'area_id' => $matchedArea->id],
                            ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                        );
                        $matStock->stock = $validated['type'] === 'out'
                            ? max(0, (float) $matStock->stock - $quantity)
                            : ((float) $matStock->stock + $quantity);
                        if ($material->meter_per_roll > 0) {
                            $matStock->total_rolls = max(0, $matStock->stock / $material->meter_per_roll);
                        } else {
                            $matStock->total_pieces = max(0, $matStock->stock);
                        }
                        $matStock->save();

                        if ($material->is_migration) {
                            $totalAreaStock = (float) \App\Models\MaterialStock::where('material_id', $material->id)->sum('stock');
                            $material->stock = max(0, $totalAreaStock);
                            $material->recalcUnitTotals()->save();
                        }
                    } else {
                        if ($validated['type'] === 'out') {
                            $material->stock = max(0, (float) $material->stock - $quantity);
                        } else {
                            $material->stock = (float) $material->stock + $quantity;
                        }
                        $material->recalcUnitTotals()->save();
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Transaksi mutasi berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat mutasi: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $transaction = MaterialTransaction::findOrFail($id);

        DB::beginTransaction();
        try {
            // Revert stock
            foreach ($transaction->items as $item) {
                $material = $item->material;
                if ($material) {
                    if ($transaction->type === 'out') {
                        // Reverting an 'out' transaction means adding stock back
                        $material->stock += $item->quantity;
                    } else {
                        // Reverting an 'in' transaction means removing stock
                        $material->stock -= $item->quantity;
                    }
                    $material->save();
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
