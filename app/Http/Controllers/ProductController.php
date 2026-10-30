<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::query();

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('supplier', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
        }

        if ($request->category && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $products = $query->with('stocks.area')->latest()->paginate(10)->withQueryString();

        $dbCategories = Material::select('category')->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->toArray();
        $defaultCategories = ['Umum', 'Kabel', 'Patchcord', 'Klem', 'Isolasi', 'ONT', 'Splitter', 'Aksesoris', 'Peralatan'];
        $categories = array_values(array_unique(array_merge($defaultCategories, $dbCategories)));
        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Inventory/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'areas' => $areas,
            'filters' => $request->only(['search', 'category'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50', // Base unit (e.g., meter, pcs, cm)
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'stock' => 'nullable|numeric|min:0',
            'initial_stock' => 'nullable|numeric|min:0',
            'area_id' => 'nullable|exists:areas,id',
            // Smart conversion fields
            'meter_per_roll' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'requires_sn' => 'boolean',
            'requires_mac' => 'boolean',
        ]);

        $areaId = $validated['area_id'] ?? null;
        unset($validated['area_id']);

        if (array_key_exists('stock', $validated) && is_null($validated['stock'])) {
            $validated['stock'] = 0;
        }

        if (!isset($validated['initial_stock']) || is_null($validated['initial_stock'])) {
            $validated['initial_stock'] = $validated['stock'] ?? 0;
        }

        $material = Material::create($validated);

        if ($areaId) {
            \App\Models\MaterialStock::create([
                'material_id' => $material->id,
                'area_id' => $areaId,
                'stock' => $material->stock,
                'initial_stock' => $material->stock,
                'total_rolls' => $material->total_rolls ?? 0,
                'total_packs' => $material->total_packs ?? 0,
                'total_pieces' => $material->total_pieces ?? 0,
            ]);
        }

        return redirect()->back()->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $product = Material::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'stock' => 'nullable|numeric|min:0',
            'initial_stock' => 'nullable|numeric|min:0',
            'area_id' => 'nullable|exists:areas,id',
            'meter_per_roll' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'requires_sn' => 'boolean',
            'requires_mac' => 'boolean',
        ]);

        $areaId = $validated['area_id'] ?? null;
        unset($validated['area_id']);

        if (array_key_exists('stock', $validated) && is_null($validated['stock'])) {
            unset($validated['stock']);
        }

        $product->update($validated);

        if ($areaId) {
            $existingStocks = \App\Models\MaterialStock::where('material_id', $product->id)->get();
            if ($existingStocks->count() <= 1) {
                \App\Models\MaterialStock::updateOrCreate(
                    ['material_id' => $product->id],
                    [
                        'area_id' => $areaId,
                        'stock' => $product->stock,
                        'initial_stock' => $product->stock,
                        'total_rolls' => $product->total_rolls ?? 0,
                        'total_packs' => $product->total_packs ?? 0,
                        'total_pieces' => $product->total_pieces ?? 0,
                    ]
                );
            } else {
                \App\Models\MaterialStock::updateOrCreate(
                    ['material_id' => $product->id, 'area_id' => $areaId],
                    [
                        'stock' => $product->stock,
                        'initial_stock' => $product->stock,
                        'total_rolls' => $product->total_rolls ?? 0,
                        'total_packs' => $product->total_packs ?? 0,
                        'total_pieces' => $product->total_pieces ?? 0,
                    ]
                );
            }
        } else {
            if (\App\Models\MaterialStock::where('material_id', $product->id)->count() === 1) {
                \App\Models\MaterialStock::where('material_id', $product->id)->delete();
            }
        }

        return redirect()->back()->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy($id)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $product = Material::findOrFail($id);
                
                // Unlink ONTs from material transaction items if any
                $itemIds = \App\Models\MaterialTransactionItem::where('material_id', $id)->pluck('id');
                if ($itemIds->isNotEmpty()) {
                    \App\Models\Ont::whereIn('material_transaction_item_id', $itemIds)->update(['material_transaction_item_id' => null]);
                    \App\Models\MaterialTransactionItem::whereIn('id', $itemIds)->delete();
                }
                
                // Hapus stok per area
                $product->stocks()->delete();
                
                // Hapus produk
                $product->delete();
            });

            return redirect()->back()->with('success', 'Produk berhasil dihapus beserta seluruh riwayatnya.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus produk: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $product = Material::with('stocks.area')->findOrFail($id);
        
        return Inertia::render('Inventory/Products/Show', [
            'product' => $product
        ]);
    }
}
