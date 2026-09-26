<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        return inertia('Settings/Areas', [
            'areas' => \App\Models\Area::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180'
        ]);

        \App\Models\Area::create($validated);

        return redirect()->back()->with('success', 'Area berhasil ditambahkan');
    }

    public function update(Request $request, \App\Models\Area $area)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name,' . $area->id,
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180'
        ]);

        $area->update($validated);

        return redirect()->back()->with('success', 'Area berhasil diubah');
    }

    public function destroy(\App\Models\Area $area)
    {
        $area->delete();
        return redirect()->back()->with('success', 'Area berhasil dihapus');
    }
}
