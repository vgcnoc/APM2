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

        $position = \App\Models\Position::create($validated);

        // Sync to Role
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => strtolower($position->name), 'guard_name' => 'web']);

        return redirect()->route('positions.index')->with('success', 'Posisi/Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, \App\Models\Position $position)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:positions,name,' . $position->id,
            'department' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $oldName = strtolower($position->name);
        $position->update($validated);

        // Sync to Role
        $role = \Spatie\Permission\Models\Role::where('name', $oldName)->where('guard_name', 'web')->first();
        if ($role) {
            $role->update(['name' => strtolower($position->name)]);
        } else {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => strtolower($position->name), 'guard_name' => 'web']);
        }

        return redirect()->route('positions.index')->with('success', 'Posisi/Jabatan berhasil diperbarui.');
    }

    public function destroy(\App\Models\Position $position)
    {
        $oldName = strtolower($position->name);
        $position->delete();

        // Sync to Role (Optional: You can choose to keep the role or delete it. Usually it's safer to leave it or delete it. Let's delete it if no users have it, or just leave it. I'll just leave it to prevent breaking users who might have the role. Or I can delete it. Better to just delete it.)
        $role = \Spatie\Permission\Models\Role::where('name', $oldName)->where('guard_name', 'web')->first();
        if ($role) {
            $role->delete();
        }
        return redirect()->route('positions.index')->with('success', 'Posisi/Jabatan berhasil dihapus.');
    }
}
