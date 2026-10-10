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

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('technician_name', 'like', "%{$request->search}%")
                  ->orWhere('transaction_number', 'like', "%{$request->search}%")
                  ->orWhere('purpose', 'like', "%{$request->search}%");
            });
        }
        
        $transactions = $query->latest()->paginate(15)->withQueryString();
        $materials = Material::orderBy('name')->get();

        return Inertia::render('Inventory/StockInOut/Index', [
            'transactions' => $transactions,
            'materials' => $materials,
            'filters' => $request->only(['search'])
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
        ]);

        DB::beginTransaction();
        try {
            // Generate Transaction Number (MUT)
            $count = MaterialTransaction::whereDate('created_at', today())->count() + 1;
            $prefix = $validated['type'] === 'out' ? 'OUT-' : 'RET-'; // Retur (In) or Out
            $transactionNumber = $prefix . now()->format('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $transaction = MaterialTransaction::create([
                'transaction_number' => $transactionNumber,
                'type' => $validated['type'],
                'date' => $validated['date'],
                'purpose' => $validated['purpose'],
                'technician_name' => $validated['technician_name'],
                'notes' => $validated['notes'],
                'user_id' => auth()->id(),
                'total_cost' => 0 
            ]);

            foreach ($validated['items'] as $item) {
                $material = Material::findOrFail($item['material_id']);
                
                // For internal stock in/out, we assume the quantity provided is in BASE UNIT
                $quantity = $item['quantity'];

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $quantity,
                    'price_per_unit' => $material->price_per_unit,
                    'total_price' => $quantity * $material->price_per_unit
                ]);

                // Update Stock
                if ($validated['type'] === 'out') {
                    $material->stock -= $quantity;
                } else {
                    $material->stock += $quantity;
                }
                $material->save();
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

            $transaction->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Transaksi mutasi berhasil dibatalkan dan stok dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membatalkan mutasi: ' . $e->getMessage());
        }
    }
}
