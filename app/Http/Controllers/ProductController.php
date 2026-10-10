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

        $products = $query->latest()->paginate(10)->withQueryString();

        $dbCategories = Material::select('category')->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->toArray();
        $defaultCategories = ['Umum', 'Kabel', 'Patchcord', 'ONT', 'Splitter', 'Aksesoris', 'Peralatan'];
        $categories = array_values(array_unique(array_merge($defaultCategories, $dbCategories)));

        return Inertia::render('Inventory/Products/Index', [
            'products' => $products,
            'categories' => $categories,
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
            // Smart conversion fields
            'meter_per_roll' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'requires_sn' => 'boolean',
            'requires_mac' => 'boolean',
        ]);

        if (array_key_exists('stock', $validated) && is_null($validated['stock'])) {
            $validated['stock'] = 0;
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
            'meter_per_roll' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'requires_sn' => 'boolean',
            'requires_mac' => 'boolean',
        ]);

        if (array_key_exists('stock', $validated) && is_null($validated['stock'])) {
            unset($validated['stock']);
        }

        $product->update($validated);

        return redirect()->back()->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy($id)
    {
        try {
            $product = Material::findOrFail($id);
            $product->delete();

            return redirect()->back()->with('success', 'Produk berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Error code 23000 usually means foreign key constraint violation
            if ($e->getCode() == '23000') {
                return redirect()->back()->with('error', 'Produk tidak dapat dihapus karena sudah digunakan dalam transaksi atau riwayat stok. Anda dapat menonaktifkan produk ini alih-alih menghapusnya.');
            }
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
