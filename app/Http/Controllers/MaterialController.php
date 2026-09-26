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
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'stock' => 'required|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        Material::create($validated);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil ditambahkan.');
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'stock' => 'required|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $material->update($validated);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        $material->delete();
        return redirect()->route('materials.index')->with('success', 'Data material berhasil dihapus.');
    }
}
