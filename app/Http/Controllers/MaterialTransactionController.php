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
        $transactions = MaterialTransaction::with(['user', 'items.material'])
            ->latest()
            ->paginate(10);

        return Inertia::render('MaterialTransactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    public function create()
    {
        $materials = Material::where('stock', '>', 0)
            ->where('category', 'not like', '%ont%')
            ->where('category', 'not like', '%modem%')
            ->where('name', 'not like', '%ont%')
            ->where('name', 'not like', '%modem%')
            ->get();
        $areas = \App\Models\Area::orderBy('name')->pluck('name')->toArray();
        $cabangs = MaterialTransaction::whereNotNull('cabang')->where('cabang', '!=', '')->distinct()->orderBy('cabang')->pluck('cabang')->toArray();
        
        return Inertia::render('MaterialTransactions/Create', [
            'materials' => $materials,
            'areas' => $areas,
            'cabangs' => $cabangs,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'technician_name' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'cabang' => 'nullable|string|max:255',
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
                'cabang' => $request->cabang,
                'area' => $request->area,
                'notes' => $request->notes,
                'user_id' => auth()->id(),
                'total_cost' => 0, // Will be updated
            ]);

            foreach ($request->items as $itemData) {
                $material = Material::findOrFail($itemData['material_id']);
                
                // Ensure sufficient stock
                if ($material->stock < $itemData['quantity']) {
                    throw new \Exception("Stok {$material->name} tidak mencukupi. Sisa stok: {$material->stock}");
                }

                $pricePerUnit = $material->price_per_unit ?? 0;
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
                
                $material->stock -= $deduction;
                
                // Recalculate total_rolls roughly
                if ($material->category === 'Kabel' && $material->meter_per_roll > 0) {
                    $material->total_rolls = $material->stock / $material->meter_per_roll;
                }
                
                $material->save();
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
        $request->validate([
            'sn_list' => 'required|string',
        ]);

        $item->load(['transaction', 'material']);
        
        $sns = array_filter(array_map('trim', preg_split('/[\n,]+/', $request->sn_list)));
        
        // Count to ensure they don't exceed quantity
        if (count($sns) > $item->quantity) {
            return back()->withErrors(['sn_list' => 'Jumlah Serial Number (' . count($sns) . ') melebihi jumlah kuantitas barang (' . $item->quantity . ').']);
        }

        foreach ($sns as $sn) {
            $existing = \App\Models\Ont::where('serial_number', $sn)->first();
            if (!$existing) {
                \App\Models\Ont::create([
                    'serial_number' => $sn,
                    'brand' => $item->material->name,
                    'status' => 'Belum Set/Baru Input',
                    'description' => "Pengambilan dari Gudang oleh: " . $item->transaction->technician_name . " (Tujuan: " . $item->transaction->purpose . ") pada " . $item->transaction->date,
                ]);
            } else {
                $existing->update([
                    'status' => 'Belum Set/Baru Input',
                    'description' => "Pengambilan Ulang dari Gudang oleh: " . $item->transaction->technician_name . " (Tujuan: " . $item->transaction->purpose . ") pada " . $item->transaction->date,
                ]);
            }
        }

        $item->update(['is_registered_to_ont' => true]);

        return back()->with('success', count($sns) . ' perangkat berhasil didaftarkan ke Menu ONT.');
    }
}
