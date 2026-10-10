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
        $defaultCategories = ['Kabel', 'Patchcord', 'ONT', 'Splitter', 'Aksesoris', 'Peralatan'];
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
            // Smart conversion fields
            'meter_per_roll' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'requires_sn' => 'boolean',
            'requires_mac' => 'boolean',
        ]);

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
            'meter_per_roll' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'requires_sn' => 'boolean',
            'requires_mac' => 'boolean',
        ]);

        $product->update($validated);

        return redirect()->back()->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $product = Material::findOrFail($id);
        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus.');
    }
}
