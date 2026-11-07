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

        $products = $query->with('stocks.area')->orderByDesc('updated_at')->orderByDesc('id')->paginate(10)->withQueryString();

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

    /**
     * Cek apakah nama produk sudah ada di sistem (untuk mendeteksi duplikasi & perbedaan harga secara realtime).
     */
    public function checkDuplicate(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        $excludeId = $request->input('exclude_id') ? (int) $request->input('exclude_id') : null;

        if (mb_strlen($name) < 2) {
            return response()->json(['exists' => false]);
        }

        $existing = \App\Services\MaterialService::findByName($name, $excludeId);

        if (!$existing) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'product' => [
                'id' => $existing->id,
                'name' => $existing->name,
                'category' => $existing->category,
                'unit' => $existing->unit,
                'stock' => (float) ($existing->stock ?? 0),
                'price_per_unit' => (float) ($existing->price_per_unit ?? 0),
                'selling_price' => (float) ($existing->selling_price ?? 0),
                'supplier' => $existing->supplier,
                'is_catalog' => (bool) $existing->is_catalog,
            ]
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
            'duplicate_action' => 'nullable|string|in:merge_update_price,merge_average_price,create_variant',
            'variant_suffix' => 'nullable|string|max:100',
        ]);

        $duplicateAction = $request->input('duplicate_action', 'merge_update_price');
        $variantSuffix = trim((string) $request->input('variant_suffix', ''));
        unset($validated['duplicate_action'], $validated['variant_suffix']);

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

        // Cek jika produk dengan nama yang sama sudah ada di sistem
        $existing = \App\Services\MaterialService::findByName($validated['name']);
        if ($existing) {
            // Opsi 3: Buat sebagai varian / batch baru dengan nama terpisah
            if ($duplicateAction === 'create_variant') {
                if ($variantSuffix === '') {
                    $variantSuffix = 'Batch ' . date('d/m/Y');
                }
                $validated['name'] = trim($validated['name']) . " ({$variantSuffix})";

                // Jika nama varian baru ini kebetulan sudah ada, gabungkan ke varian tersebut
                $existingVariant = \App\Services\MaterialService::findByName($validated['name']);
                if ($existingVariant) {
                    $validated['is_catalog'] = true;
                    $validated['is_migration'] = false;
                    \App\Services\MaterialService::addStockToExistingMaterial($existingVariant, $validated, null);
                    return redirect()->back()->with('success', "Stok berhasil ditambahkan ke varian yang sudah ada: '{$validated['name']}'.");
                }

                Material::create($validated);
                return redirect()->back()->with('success', "Produk baru berhasil dibuat sebagai varian: '{$validated['name']}'.");
            }

            // Opsi 2: Hitung Harga Rata-Rata Tertimbang (Moving Average Cost)
            if ($duplicateAction === 'merge_average_price') {
                $oldStock = max(0.0, (float) ($existing->stock ?? 0));
                $oldPrice = (float) ($existing->price_per_unit ?? 0);
                $newStock = max(0.0, (float) ($validated['stock'] ?? 0));
                $newPrice = (float) ($validated['price_per_unit'] ?? 0);

                if (($oldStock + $newStock) > 0 && ($oldPrice > 0 || $newPrice > 0)) {
                    $totalValue = ($oldStock * $oldPrice) + ($newStock * $newPrice);
                    $avgPrice = $totalValue / ($oldStock + $newStock);
                    $validated['price_per_unit'] = round($avgPrice, 2);
                }
            }

            // Opsi 1 (default) & Opsi 2: Gabungkan stok ke material target
            $validated['is_catalog'] = true;
            $validated['is_migration'] = false;
            \App\Services\MaterialService::addStockToExistingMaterial($existing, $validated, null);

            $msg = $duplicateAction === 'merge_average_price'
                ? "Produk '{$existing->name}' berhasil digabungkan dengan HPP Rata-Rata Rp " . number_format($validated['price_per_unit'] ?? 0, 0, ',', '.') . "."
                : "Produk '{$existing->name}' sudah ada di sistem. Stok berhasil digabungkan dan harga diperbarui.";

            return redirect()->back()->with('success', $msg);
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

        // Cek jika nama diubah menimpa produk/material lain yang sudah ada sebelumnya
        $existing = \App\Services\MaterialService::findByName($validated['name'], $product->id);
        if ($existing) {
            \App\Services\MaterialService::mergeMaterials($existing, $product, $validated, null, $validated['stock'] ?? null);
            return redirect()->back()->with('success', 'Produk berhasil diperbarui dan menimpa data material sebelumnya.');
        }

        $validated['is_catalog'] = true;
        $validated['is_migration'] = false;
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
                            'is_catalog' => false,
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
