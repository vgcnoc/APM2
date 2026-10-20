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

        // 1. Pencarian Cerdas (Nama, Kategori, Supplier, Satuan, Keterangan)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. Filter Kategori
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // 3. Filter Satuan (Unit)
        if ($request->filled('unit')) {
            $query->where('unit', $request->unit);
        }

        // 4. Filter Area Gudang
        if ($request->filled('area_id')) {
            $areaId = $request->area_id;
            $query->whereHas('stocks', function ($q) use ($areaId) {
                $q->where('area_id', $areaId)->where('stock', '>', 0);
            });
        }

        // 5. Filter Status Stok
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'out_of_stock') {
                $query->whereDoesntHave('stocks', function ($sq) {
                    $sq->where('stock', '>', 0);
                });
            } elseif ($request->stock_status === 'available') {
                $query->whereHas('stocks', function ($sq) {
                    $sq->where('stock', '>', 0);
                });
            } elseif ($request->stock_status === 'low_stock') {
                $query->whereHas('stocks', function ($sq) {
                    $sq->where('stock', '>', 0)->where('stock', '<=', 20);
                });
            }
        }

        // 6. Pengurutan (Sorting)
        $sortBy = $request->get('sort_by', 'name_asc');
        switch ($sortBy) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'category':
                $query->orderBy('category')->orderBy('name');
                break;
            case 'name_asc':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        $materials = $query->paginate(15)->withQueryString();

        $categories = \App\Models\Material::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->sort()->values();
        $units = \App\Models\Material::whereNotNull('unit')->where('unit', '!=', '')->distinct()->pluck('unit')->sort()->values();
        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        // Summary counts
        $totalMaterials = \App\Models\Material::count();
        $outOfStockCount = \App\Models\Material::whereDoesntHave('stocks', function ($sq) {
            $sq->where('stock', '>', 0);
        })->count();
        $availableCount = \App\Models\Material::whereHas('stocks', function ($sq) {
            $sq->where('stock', '>', 0);
        })->count();

        return Inertia::render('Inventory/Warehouse/Index', [
            'materials' => $materials,
            'categories' => $categories,
            'units' => $units,
            'areas' => $areas,
            'filters' => $request->only(['search', 'category', 'unit', 'area_id', 'stock_status', 'sort_by']),
            'summary' => [
                'total_materials' => $totalMaterials,
                'out_of_stock' => $outOfStockCount,
                'available' => $availableCount,
                'filtered_count' => $materials->total(),
            ]
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
