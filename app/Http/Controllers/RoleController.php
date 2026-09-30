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
        $allPermissions = Permission::all();
        
        // Group permissions logically
        $groupedPermissions = [];
        
        foreach ($allPermissions as $perm) {
            if (str_starts_with($perm->name, 'menu_')) {
                // It's a menu permission, use it as a base group
                $groupName = $perm->name;
                if (!isset($groupedPermissions[$groupName])) {
                    $groupedPermissions[$groupName] = ['menu' => $perm, 'actions' => []];
                } else {
                    $groupedPermissions[$groupName]['menu'] = $perm;
                }
            } else {
                // It's an action permission, try to find its parent menu
                // e.g. customers_booking_create -> menu_customers_booking
                $parts = explode('_', $perm->name);
                $action = array_pop($parts); // create, edit, delete
                $base = implode('_', $parts); // customers_booking
                $expectedMenu = 'menu_' . $base;
                
                if (isset($groupedPermissions[$expectedMenu])) {
                    $groupedPermissions[$expectedMenu]['actions'][] = $perm;
                } else {
                    // Try generic (like customers_create -> belongs to all customers menus? Or just create a group for it)
                    $genericMenu = 'menu_' . $base;
                    if (!isset($groupedPermissions[$genericMenu])) {
                        $groupedPermissions[$genericMenu] = ['menu' => null, 'actions' => []];
                    }
                    $groupedPermissions[$genericMenu]['actions'][] = $perm;
                }
            }
        }

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'permissions' => $groupedPermissions
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
