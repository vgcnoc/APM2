<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        // Only get transactions with purpose indicating it's a purchase
        $transactions = MaterialTransaction::where('purpose', 'Pembelian Toko / Supplier')
                            ->with('items.material')
                            ->latest()
                            ->paginate(15);

        $materials = Material::orderBy('name')->get();
        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Inventory/PurchaseOrders/Index', [
            'transactions' => $transactions,
            'materials' => $materials,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'area_name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.purchase_unit' => 'required|string', // e.g., 'roll', 'pack', 'pcs', 'meter', 'cm'
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);
        $validated['date'] = $validated['date'] ?? now()->toDateString();

        DB::beginTransaction();
        try {
            // Generate Transaction Number (PO)
            $count = MaterialTransaction::whereDate('created_at', today())->count() + 1;
            $transactionNumber = 'PO-' . now()->format('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $transaction = MaterialTransaction::create([
                'transaction_number' => $transactionNumber,
                'type' => 'in',
                'date' => $validated['date'],
                'purpose' => 'Pembelian Toko / Supplier',
                'technician_name' => $validated['area_name'], // Reusing this column for area/wilayah name
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'total_cost' => 0 // Will calculate below
            ]);

            $totalCost = 0;

            foreach ($validated['items'] as $item) {
                $material = Material::findOrFail($item['material_id']);
                
                // Smart Unit Conversion Logic
                $convertedQuantity = $item['quantity']; // Default 1:1
                
                if ($item['purchase_unit'] === 'roll' && $material->meter_per_roll > 0) {
                    $convertedQuantity = $item['quantity'] * $material->meter_per_roll;
                } elseif ($material->category === 'Isolasi' && $item['purchase_unit'] === 'pack') {
                    $convertedQuantity = $item['quantity'] * (($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1) * ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50));
                } elseif ($item['purchase_unit'] === 'pack' && $material->pcs_per_pack > 0) {
                    $convertedQuantity = $item['quantity'] * $material->pcs_per_pack;
                } elseif ($item['purchase_unit'] === 'pcs' && $material->cm_per_pcs > 0) {
                    $convertedQuantity = $item['quantity'] * $material->cm_per_pcs;
                }

                if (isset($item['price']) && $item['price'] !== null && $item['price'] !== '') {
                    $pricePerUnit = $item['price'];
                    $itemTotal = $item['quantity'] * $item['price'];
                } else {
                    // Fallback: harga dasar material per satuan stok
                    $pricePerUnit = $material->price_per_unit ?? 0;
                    $itemTotal = $convertedQuantity * $pricePerUnit;
                }
                $totalCost += $itemTotal;

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $convertedQuantity, // The quantity in BASE UNIT
                    'price_per_unit' => $pricePerUnit,
                    'total_price' => $itemTotal
                ]);

                // Increase Stock
                $material->stock += $convertedQuantity;
                $material->save();
            }

            $transaction->update(['total_cost' => $totalCost]);

            DB::commit();
            return redirect()->back()->with('success', 'Order Toko berhasil dicatat dan stok telah bertambah.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat order: ' . $e->getMessage());
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
                    $material->stock -= $item->quantity;
                    $material->save();
                }
            }

            $transaction->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Order Toko berhasil dihapus dan stok telah dikurangi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus order: ' . $e->getMessage());
        }
    }
}
