<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseStockController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Material::with(['stocks.area']);

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
        }

        $materials = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Inventory/Warehouse/Index', [
            'materials' => $materials,
            'filters' => $request->only(['search'])
        ]);
    }

    public function destroy($id)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $material = \App\Models\Material::findOrFail($id);

                // Unlink ONTs from material transaction items if any
                $itemIds = \App\Models\MaterialTransactionItem::where('material_id', $id)->pluck('id');
                if ($itemIds->isNotEmpty()) {
                    \App\Models\Ont::whereIn('material_transaction_item_id', $itemIds)->update(['material_transaction_item_id' => null]);
                    \App\Models\MaterialTransactionItem::whereIn('id', $itemIds)->delete();
                }

                // Hapus stok per area
                $material->stocks()->delete();

                // Hapus material
                $material->delete();
            });

            return redirect()->back()->with('success', 'Barang berhasil dihapus dari inventory.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus barang: ' . $e->getMessage());
        }
    }
}
