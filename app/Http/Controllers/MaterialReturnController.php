<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialReturnController extends Controller
{
    /**
     * Halaman utama Retur Material — menampilkan stok area & riwayat retur
     */
    public function index(Request $request)
    {
        // --- Stok Area (Material yang bisa di-retur) ---
        $areaStockQuery = MaterialStock::with(['material', 'area'])
            ->where('stock', '>', 0);

        if ($request->area_id) {
            $areaStockQuery->where('area_id', $request->area_id);
        }

        if ($request->search) {
            $areaStockQuery->whereHas('material', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }

        // Non-admin: filter hanya area yang bisa diakses
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('material_transactions_view_all')) {
            if (auth()->user()->can('material_transactions_view_area')) {
                $areaStockQuery->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            }
        }

        $areaStocks = $areaStockQuery->get()->map(function ($stock) {
            $material = $stock->material;
            $displayUnit = $material->unit ?? 'pcs';
            $displayStock = $stock->stock;

            // Konversi tampilan sesuai kategori
            if (str_contains(strtolower($material->category), 'kabel')) {
                $displayUnit = 'meter';
                $displayStock = $stock->stock; // stock already in meters
            } elseif ($material->category === 'Paku Klem') {
                $displayUnit = 'pcs';
                $displayStock = $stock->stock;
            } elseif ($material->category === 'Isolasi') {
                $displayUnit = 'cm';
                $displayStock = $stock->stock;
            }

            return [
                'id' => $stock->id,
                'material_id' => $stock->material_id,
                'area_id' => $stock->area_id,
                'material_name' => $material->name,
                'material_category' => $material->category,
                'area_name' => $stock->area->name ?? '-',
                'stock' => $displayStock,
                'display_unit' => $displayUnit,
                'raw_stock' => $stock->stock,
                'meter_per_roll' => $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000,
                'pcs_per_pack' => $material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1,
                'cm_per_pcs' => $material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50,
            ];
        });

        // --- Riwayat Retur ---
        $returnQuery = MaterialTransaction::with(['user', 'items.material', 'areaModel'])
            ->where('type', 'return');

        if ($request->area_id) {
            $returnQuery->where('area_id', $request->area_id);
        }

        if ($request->return_search) {
            $returnQuery->where(function ($q) use ($request) {
                $q->where('transaction_number', 'like', '%' . $request->return_search . '%')
                  ->orWhere('technician_name', 'like', '%' . $request->return_search . '%');
            });
        }

        if ($request->start_date) {
            $returnQuery->whereDate('date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $returnQuery->whereDate('date', '<=', $request->end_date);
        }

        // Non-admin filter
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('material_transactions_view_all')) {
            if (auth()->user()->can('material_transactions_view_area')) {
                $returnQuery->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                $returnQuery->where('user_id', auth()->id());
            }
        }

        $returns = $returnQuery->latest()->paginate(15)->withQueryString();

        // Summary cards
        $totalReturns = MaterialTransaction::where('type', 'return')->count();
        $totalReturnedItems = MaterialTransactionItem::whereHas('transaction', fn($q) => $q->where('type', 'return'))->sum('quantity');

        $areas = Area::orderBy('name')->get();

        return Inertia::render('MaterialReturns/Index', [
            'areaStocks' => $areaStocks,
            'returns' => $returns,
            'filters' => $request->only(['search', 'area_id', 'return_search', 'start_date', 'end_date']),
            'areas' => $areas,
            'summary' => [
                'total_returns' => $totalReturns,
                'total_returned_items' => $totalReturnedItems,
            ],
        ]);
    }

    /**
     * Proses Retur: pindahkan stok dari Area kembali ke Gudang Utama
     */
    public function store(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'technician_name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($request) {
            $area = Area::findOrFail($request->area_id);

            $transaction = MaterialTransaction::create([
                'transaction_number' => 'RTR-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'type' => 'return',
                'date' => now()->toDateString(),
                'technician_name' => $request->technician_name,
                'purpose' => 'Retur Sisa Material ke Gudang dari ' . $area->name,
                'area_id' => $request->area_id,
                'notes' => $request->notes,
                'user_id' => auth()->id(),
                'total_cost' => 0,
            ]);

            $totalCost = 0;

            foreach ($request->items as $itemData) {
                $material = Material::findOrFail($itemData['material_id']);
                $materialStock = MaterialStock::where('material_id', $material->id)
                    ->where('area_id', $request->area_id)
                    ->first();

                if (!$materialStock || $materialStock->stock < $itemData['quantity']) {
                    $available = $materialStock ? $materialStock->stock : 0;
                    throw new \Exception("Stok {$material->name} di area {$area->name} tidak mencukupi. Sisa: {$available}");
                }

                $pricePerUnit = $material->selling_price ?? 0;

                // Hitung harga per unit kecil sesuai kategori
                if (str_contains(strtolower($material->category), 'kabel') && strtolower($itemData['unit'] ?? '') === 'meter') {
                    $meterPerRoll = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000;
                    $pricePerUnit = $pricePerUnit / $meterPerRoll;
                }
                if ($material->category === 'Paku Klem' && strtolower($itemData['unit'] ?? '') === 'pcs') {
                    $pcsPerPack = $material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1;
                    $pricePerUnit = $pricePerUnit / $pcsPerPack;
                }
                if ($material->category === 'Isolasi' && strtolower($itemData['unit'] ?? '') === 'cm') {
                    $cmPerPcs = $material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50;
                    $pricePerUnit = $pricePerUnit / $cmPerPcs;
                }

                $totalPrice = $itemData['quantity'] * $pricePerUnit;
                $totalCost += $totalPrice;

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? $material->unit,
                    'price_per_unit' => $pricePerUnit,
                    'total_price' => $totalPrice,
                ]);

                // Hitung pengurangan Stok Area (dalam satuan dasar: meter/pcs/cm)
                $deduction = $itemData['quantity'];
                if (str_contains(strtolower($material->category), 'kabel') && ($itemData['unit'] === 'roll' || $itemData['unit'] === 'rol')) {
                    $deduction = $itemData['quantity'] * ($material->meter_per_roll > 0 ? $material->meter_per_roll : 1000);
                }
                if ($material->category === 'Paku Klem' && ($itemData['unit'] === 'pack' || $itemData['unit'] === 'bungkus')) {
                    $deduction = $itemData['quantity'] * ($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1);
                }
                if ($material->category === 'Isolasi' && ($itemData['unit'] === 'pcs')) {
                    $deduction = $itemData['quantity'] * ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50);
                }

                if ($materialStock->stock < $deduction) {
                    throw new \Exception("Stok {$material->name} di area {$area->name} tidak mencukupi untuk retur ini.");
                }

                // Kurangi Stok Area
                $materialStock->stock -= $deduction;

                // Tambah Stok Gudang Utama
                $material->stock += $deduction;

                // Recalculate rolls/packs/pieces
                if (str_contains(strtolower($material->category), 'kabel') && $material->meter_per_roll > 0) {
                    $material->total_rolls = $material->stock / $material->meter_per_roll;
                    $materialStock->total_rolls = $materialStock->stock / $material->meter_per_roll;
                }
                if ($material->category === 'Paku Klem' && $material->pcs_per_pack > 0) {
                    $material->total_packs = $material->stock / $material->pcs_per_pack;
                    $materialStock->total_packs = $materialStock->stock / $material->pcs_per_pack;
                }
                if ($material->category === 'Isolasi' && $material->cm_per_pcs > 0) {
                    $material->total_pieces = $material->stock / $material->cm_per_pcs;
                    $materialStock->total_pieces = $materialStock->stock / $material->cm_per_pcs;
                }

                $material->save();
                $materialStock->save();
            }

            $transaction->update(['total_cost' => $totalCost]);
        });

        return redirect()->route('material-returns.index')
            ->with('success', 'Retur material berhasil diproses! Stok telah dikembalikan ke Gudang Utama.');
    }

    /**
     * Hapus Retur (rollback stok)
     */
    public function destroy(MaterialTransaction $materialReturn)
    {
        if ($materialReturn->type !== 'return') {
            return redirect()->back()->with('error', 'Transaksi ini bukan retur.');
        }

        try {
            DB::transaction(function () use ($materialReturn) {
                foreach ($materialReturn->items as $item) {
                    $material = $item->material;
                    if ($material) {
                        // Rollback: kembalikan stok ke area, kurangi dari gudang
                        $material->stock -= $item->quantity;
                        
                        $materialStock = MaterialStock::firstOrCreate(
                            ['material_id' => $material->id, 'area_id' => $materialReturn->area_id],
                            ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                        );
                        $materialStock->stock += $item->quantity;

                        // Recalculate
                        if (str_contains(strtolower($material->category), 'kabel') && $material->meter_per_roll > 0) {
                            $material->total_rolls = $material->stock / $material->meter_per_roll;
                            $materialStock->total_rolls = $materialStock->stock / $material->meter_per_roll;
                        }
                        if ($material->category === 'Paku Klem' && $material->pcs_per_pack > 0) {
                            $material->total_packs = $material->stock / $material->pcs_per_pack;
                            $materialStock->total_packs = $materialStock->stock / $material->pcs_per_pack;
                        }
                        if ($material->category === 'Isolasi' && $material->cm_per_pcs > 0) {
                            $material->total_pieces = $material->stock / $material->cm_per_pcs;
                            $materialStock->total_pieces = $materialStock->stock / $material->cm_per_pcs;
                        }

                        $material->save();
                        $materialStock->save();
                    }
                }

                $materialReturn->items()->delete();
                $materialReturn->delete();
            });

            return redirect()->route('material-returns.index')
                ->with('success', 'Retur berhasil dibatalkan dan stok telah dikembalikan ke Area.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan retur: ' . $e->getMessage());
        }
    }
}
