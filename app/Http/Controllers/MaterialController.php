<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::query();
        
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
        }

        $materials = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Materials/Index', [
            'materials' => $materials,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'meter_per_roll' => 'nullable|numeric|min:0',
            'total_rolls' => 'nullable|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['initial_stock'] = $validated['stock'];

        Material::create($validated);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil ditambahkan.');
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'meter_per_roll' => 'nullable|numeric|min:0',
            'total_rolls' => 'nullable|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $material->update($validated);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        try {
            $material->delete();
            return redirect()->route('materials.index')->with('success', 'Data material berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == "23000") {
                return redirect()->route('materials.index')->with('error', 'Data material tidak dapat dihapus karena sudah digunakan dalam Riwayat Order/Pengambilan.');
            }
            return redirect()->route('materials.index')->with('error', 'Terjadi kesalahan saat menghapus data material.');
        }
    }

    public function addStock(Request $request, Material $material)
    {
        $validated = $request->validate([
            'added_stock' => 'required|numeric|min:0.01',
            'added_rolls' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        $material->stock += $validated['added_stock'];
        $material->initial_stock += $validated['added_stock'];

        if ($material->category === 'Kabel' && !empty($validated['added_rolls'])) {
            $material->total_rolls += $validated['added_rolls'];
        }

        if (isset($validated['price_per_unit'])) {
            $material->price_per_unit = $validated['price_per_unit'];
        }
        
        if (isset($validated['selling_price'])) {
            $material->selling_price = $validated['selling_price'];
        }

        $material->save();

        return redirect()->route('materials.index')->with('success', "Berhasil menambahkan stok masuk untuk {$material->name}.");
    }
}
