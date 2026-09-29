<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::with('stocks.area');
        
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
        }

        $materials = $query->latest()->paginate(10)->withQueryString();

        $areas = \App\Models\Area::orderBy('name')->get();

        return Inertia::render('Materials/Index', [
            'materials' => $materials,
            'areas' => $areas,
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
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'total_packs' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'total_pieces' => 'nullable|numeric|min:0',
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
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'total_packs' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'total_pieces' => 'nullable|numeric|min:0',
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
            'area_id' => 'required|exists:areas,id',
            'added_stock' => 'required|numeric|min:0.01',
            'added_rolls' => 'nullable|numeric|min:0',
            'added_packs' => 'nullable|numeric|min:0',
            'added_pieces' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        // Update global stock
        $material->stock += $validated['added_stock'];
        $material->initial_stock += $validated['added_stock'];

        // Update Area stock
        $materialStock = \App\Models\MaterialStock::firstOrCreate(
            ['material_id' => $material->id, 'area_id' => $validated['area_id']],
            ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
        );
        $materialStock->stock += $validated['added_stock'];
        $materialStock->initial_stock += $validated['added_stock'];

        if ($material->category === 'Kabel' && !empty($validated['added_rolls'])) {
            $material->total_rolls += $validated['added_rolls'];
            $materialStock->total_rolls += $validated['added_rolls'];
        }

        if ($material->category === 'Paku Klem' && !empty($validated['added_packs'])) {
            $material->total_packs += $validated['added_packs'];
            $materialStock->total_packs += $validated['added_packs'];
        }

        if ($material->category === 'Isolasi' && !empty($validated['added_pieces'])) {
            $material->total_pieces += $validated['added_pieces'];
            $materialStock->total_pieces += $validated['added_pieces'];
        }
        
        $materialStock->save();

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
