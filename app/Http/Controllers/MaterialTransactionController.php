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
        $query = MaterialTransaction::with(['user', 'items.material', 'areaModel']);

        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('material_transactions_view_all')) {
            if (auth()->user()->can('material_transactions_view_area')) {
                $query->where('area_id', auth()->user()->area_id);
            } else {
                $query->where('user_id', auth()->id());
            }
        }

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
        $baseQuery = MaterialTransaction::query();
        
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('material_transactions_view_all')) {
            if (auth()->user()->can('material_transactions_view_area')) {
                $baseQuery->where('area_id', auth()->user()->area_id);
            } else {
                $baseQuery->where('user_id', auth()->id());
            }
        }

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
        $totalCost = (clone $baseQuery)->sum('total_cost');
        
        // Total Items
        $totalItems = MaterialTransactionItem::whereHas('transaction', function($q) use ($request) {
            if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('material_transactions_view_all')) {
                if (auth()->user()->can('material_transactions_view_area')) {
                    $q->where('area_id', auth()->user()->area_id);
                } else {
                    $q->where('user_id', auth()->id());
                }
            }
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
        $technicians = MaterialTransaction::select('technician_name')->distinct()->whereNotNull('technician_name')->pluck('technician_name');
        
        $areas = \App\Models\Area::orderBy('name')->get();

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
        ]);
    }

    public function create()
    {
        $materials = Material::with('stocks')->get();
        $areas = \App\Models\Area::orderBy('name')->get();
        
        return Inertia::render('MaterialTransactions/Create', [
            'materials' => $materials,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request)
    {
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

        DB::transaction(function () use ($request) {
            $totalCost = 0;
            
            // Create Transaction
            $transaction = MaterialTransaction::create([
                'transaction_number' => 'OUT-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'type' => 'out',
                'date' => $request->date,
                'technician_name' => $request->technician_name,
                'purpose' => $request->purpose,
                'area_id' => $request->area_id,
                'notes' => $request->notes,
                'user_id' => auth()->id(),
                'total_cost' => 0, // Will be updated
            ]);

            foreach ($request->items as $itemData) {
                $material = Material::findOrFail($itemData['material_id']);
                
                $materialStock = \App\Models\MaterialStock::firstOrCreate(
                    ['material_id' => $material->id, 'area_id' => $request->area_id],
                    ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                );

                // Ensure sufficient stock in global inventory
                if ($material->stock < $itemData['quantity']) {
                    throw new \Exception("Stok {$material->name} di Gudang Utama tidak mencukupi. Sisa stok: {$material->stock}");
                }

                $pricePerUnit = $material->selling_price ?? 0;
                
                // Jika barang adalah Kabel dan satuan yang diambil adalah meter, 
                // hitung harga jual per meter (harga 1 roll dibagi panjang 1 roll)
                if ($material->category === 'Kabel' && strtolower($itemData['unit'] ?? '') === 'meter') {
                    $meterPerRoll = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000;
                    $pricePerUnit = $pricePerUnit / $meterPerRoll;
                }
                
                // Jika barang adalah Paku Klem, hitung harga jual per pcs (harga 1 bungkus dibagi isi)
                if ($material->category === 'Paku Klem' && strtolower($itemData['unit'] ?? '') === 'pcs') {
                    $pcsPerPack = $material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1;
                    $pricePerUnit = $pricePerUnit / $pcsPerPack;
                }
                
                // Jika barang adalah Isolasi, hitung harga jual per cm (harga 1 pcs dibagi panjang cm)
                if ($material->category === 'Isolasi' && strtolower($itemData['unit'] ?? '') === 'cm') {
                    $cmPerPcs = $material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50;
                    $pricePerUnit = $pricePerUnit / $cmPerPcs;
                }

                $totalPrice = $itemData['quantity'] * $pricePerUnit;
                $totalCost += $totalPrice;

                // Create Item
                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? $material->unit,
                    'price_per_unit' => $pricePerUnit,
                    'total_price' => $totalPrice,
                ]);

                // Deduct Stock
                $deduction = $itemData['quantity'];
                if ($material->category === 'Kabel' && ($itemData['unit'] === 'roll' || $itemData['unit'] === 'rol')) {
                    $deduction = $itemData['quantity'] * ($material->meter_per_roll > 0 ? $material->meter_per_roll : 1000);
                }
                if ($material->category === 'Paku Klem' && ($itemData['unit'] === 'pack' || $itemData['unit'] === 'bungkus')) {
                    $deduction = $itemData['quantity'] * ($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1);
                }
                if ($material->category === 'Isolasi' && ($itemData['unit'] === 'pcs')) {
                    $deduction = $itemData['quantity'] * ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50);
                }
                
                $material->stock -= $deduction;
                $materialStock->stock += $deduction;
                
                // Recalculate total_rolls roughly
                if ($material->category === 'Kabel' && $material->meter_per_roll > 0) {
                    $material->total_rolls = $material->stock / $material->meter_per_roll;
                    $materialStock->total_rolls = $materialStock->stock / $material->meter_per_roll;
                }
                
                // Recalculate total_packs roughly
                if ($material->category === 'Paku Klem' && $material->pcs_per_pack > 0) {
                    $material->total_packs = $material->stock / $material->pcs_per_pack;
                    $materialStock->total_packs = $materialStock->stock / $material->pcs_per_pack;
                }

                // Recalculate total_pieces roughly
                if ($material->category === 'Isolasi' && $material->cm_per_pcs > 0) {
                    $material->total_pieces = $material->stock / $material->cm_per_pcs;
                    $materialStock->total_pieces = $materialStock->stock / $material->cm_per_pcs;
                }
                
                $material->save();
                $materialStock->save();
            }

            $transaction->update(['total_cost' => $totalCost]);
        });

        return redirect()->route('material-transactions.index')
            ->with('success', 'Order/Pengambilan Barang berhasil dicatat dan stok telah dikurangi.');
    }

    public function show(MaterialTransaction $materialTransaction)
    {
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
        try {
            DB::transaction(function () use ($materialTransaction) {
                // Restore stock
                foreach ($materialTransaction->items as $item) {
                    $material = $item->material;
                    if ($material) {
                        $addition = $item->quantity;
                        if ($material->category === 'Kabel' && ($item->unit === 'roll' || $item->unit === 'rol')) {
                            $addition = $item->quantity * ($material->meter_per_roll > 0 ? $material->meter_per_roll : 1000);
                        }
                        if ($material->category === 'Paku Klem' && ($item->unit === 'pack' || $item->unit === 'bungkus')) {
                            $addition = $item->quantity * ($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1);
                        }
                        if ($material->category === 'Isolasi' && ($item->unit === 'pcs')) {
                            $addition = $item->quantity * ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50);
                        }
                        $materialStock = \App\Models\MaterialStock::firstOrCreate(
                            ['material_id' => $material->id, 'area_id' => $materialTransaction->area_id],
                            ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                        );

                        $material->stock += $addition;
                        $materialStock->stock += $addition;
                        
                        if ($material->category === 'Kabel' && $material->meter_per_roll > 0) {
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
                
                // Delete the transaction (cascade will delete items)
                $materialTransaction->delete();
            });

            return redirect()->route('material-transactions.index')
                ->with('success', 'Riwayat Order berhasil dihapus dan stok telah dikembalikan.');
        } catch (\Exception $e) {
            return redirect()->route('material-transactions.index')
                ->with('error', 'Gagal menghapus riwayat order: ' . $e->getMessage());
        }
    }
}
