<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function($item) {
            return explode('_', $item->name)[0];
        });

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = Role::create(['name' => strtolower($request->name)]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', 'Role berhasil ditambahkan');
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'array'
        ]);

        // prevent editing admin role name
        if ($role->name === 'admin' && $request->name !== 'admin') {
            return redirect()->back()->with('error', 'Nama role admin tidak dapat diubah');
        }

        $role->update(['name' => strtolower($request->name)]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', 'Role berhasil diperbarui');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->back()->with('error', 'Role admin tidak dapat dihapus');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role berhasil dihapus');
    }
}
