<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Position::query();

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('department', 'like', "%{$request->search}%");
        }

        $positions = $query->orderBy('name')->paginate(15)->withQueryString();

        return \Inertia\Inertia::render('Positions/Index', [
            'positions' => $positions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:positions,name',
            'department' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        \App\Models\Position::create($validated);

        return redirect()->route('positions.index')->with('success', 'Posisi/Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, \App\Models\Position $position)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:positions,name,' . $position->id,
            'department' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $position->update($validated);

        return redirect()->route('positions.index')->with('success', 'Posisi/Jabatan berhasil diperbarui.');
    }

    public function destroy(\App\Models\Position $position)
    {
        $position->delete();
        return redirect()->route('positions.index')->with('success', 'Posisi/Jabatan berhasil dihapus.');
    }
}
