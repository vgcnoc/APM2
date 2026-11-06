<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Khusus material murni katalog (Gudang Pusat) - Material dari hasil migrasi data pelanggan TIDAK dimunculkan
        $query = Material::catalogProducts();

        if ($request->search) {
            $query->where(function ($sq) use ($request) {
                $sq->where('name', 'like', "%{$request->search}%")
                   ->orWhere('supplier', 'like', "%{$request->search}%")
                   ->orWhere('category', 'like', "%{$request->search}%");
            });
        }

        if ($request->category && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $products = $query->with('stocks.area')->latest()->paginate(10)->withQueryString();

        $dbCategories = Material::catalogProducts()->select('category')->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->toArray();
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

        $validated['is_migration'] = false;
        $validated['is_catalog'] = true; // Produk katalog toko (master data produk)

        // Normalisasi nama ke bentuk kanonikal
        $canonicalName = \App\Services\MaterialService::normalizeMaterialName($validated['name']);
        if ($canonicalName !== '') {
            $validated['name'] = $canonicalName;
        }

        // Cek jika produk dengan nama yang sama sudah ada di sistem
        $existing = \App\Services\MaterialService::findByName($validated['name']);
        if ($existing) {
            \App\Services\MaterialService::addStockToExistingMaterial($existing, $validated, null);
            return redirect()->back()->with('success', "Produk sudah ada ({$existing->name}). Stok dan data berhasil digabungkan.");
        }

        Material::create($validated);

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

        unset($validated['area_id']);

        if (array_key_exists('stock', $validated) && is_null($validated['stock'])) {
            unset($validated['stock']);
        }

        // Normalisasi nama ke bentuk kanonikal
        $canonicalName = \App\Services\MaterialService::normalizeMaterialName($validated['name']);
        if ($canonicalName !== '') {
            $validated['name'] = $canonicalName;
        }

        // Cek jika nama diubah menimpa produk/material lain yang sudah ada sebelumnya
        $existing = \App\Services\MaterialService::findByName($validated['name'], $product->id);
        if ($existing) {
            \App\Services\MaterialService::mergeMaterials($existing, $product, $validated, null, $validated['stock'] ?? null);
            return redirect()->back()->with('success', 'Produk berhasil diperbarui dan menimpa data material sebelumnya.');
        }

        $validated['is_catalog'] = true;
        $product->update($validated);

        return redirect()->back()->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy($id)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $product = Material::findOrFail($id);

                $itemIds = \App\Models\MaterialTransactionItem::where('material_id', $id)->pluck('id');
                if ($itemIds->isNotEmpty()) {
                    $normalizedName = trim(preg_replace('/^test\s+/i', '', trim($product->name)));
                    $replacement = Material::where('id', '!=', $product->id)
                        ->where('id', '>', 260)
                        ->where(function ($q) {
                            $q->where('is_active', true)->orWhereNull('is_active');
                        })
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($normalizedName)])
                        ->first();

                    if ($replacement) {
                        \App\Models\MaterialTransactionItem::whereIn('id', $itemIds)->update([
                            'material_id' => $replacement->id,
                        ]);
                        \App\Models\Ont::where('material_id', $product->id)->update([
                            'material_id' => $replacement->id,
                        ]);
                        $product->stocks()->delete();
                        $product->delete();
                    } else {
                        $product->update([
                            'is_active' => false,
                            'stock' => 0,
                            'total_rolls' => 0,
                            'total_packs' => 0,
                            'total_pieces' => 0,
                        ]);
                        $product->stocks()->update([
                            'stock' => 0,
                            'total_rolls' => 0,
                            'total_packs' => 0,
                            'total_pieces' => 0,
                        ]);
                    }
                } else {
                    $product->stocks()->delete();
                    $product->delete();
                }
            });

            return redirect()->back()->with('success', 'Produk berhasil dihapus.');
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
