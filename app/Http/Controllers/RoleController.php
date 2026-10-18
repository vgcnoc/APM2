<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Master list of all permissions grouped by module.
     * Categorized by 5 types: menu, card, tab, filter, button, feature.
     */
    public static function getPermissionRegistry(): array
    {
        return \App\Support\PermissionRegistry::get();
    }

    /**
     * Ensure all permissions from registry exist in DB efficiently in bulk.
     */
    public function syncPermissionsToDb(): void
    {
        $allNames = \App\Support\PermissionRegistry::allNames();
        $existing = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        $missing = array_diff($allNames, $existing);
        if (!empty($missing)) {
            $records = array_map(fn($name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ], array_values($missing));
            Permission::insert($records);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    public function index(Request $request)
    {
        // Auto-sync permissions to DB on page load
        $this->syncPermissionsToDb();

        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => $role->users()->count(),
            ];
        });

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'permissionGroups' => $this->getPermissionRegistry(),
            'rolePresets' => \App\Support\PermissionRegistry::getDefaultRolePresets(),
            'positions' => \App\Models\Position::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => strtolower(trim($request->name)), 'guard_name' => 'web']);

        if ($request->has('permissions') && is_array($request->permissions)) {
            $role->syncPermissions($request->permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'array',
        ]);

        if ($role->name === 'admin') {
            if ($request->name !== 'admin') {
                return redirect()->back()->with('error', 'Nama role Admin tidak dapat diubah.');
            }
            $role->syncPermissions(Permission::where('guard_name', 'web')->get());
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            return redirect()->back()->with('success', "Role Admin terkunci permanen dengan akses penuh.");
        }

        $role->update(['name' => strtolower(trim($request->name))]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil diperbarui.");
    }

    public function clone(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
        ]);

        $newRole = Role::create(['name' => strtolower($request->name), 'guard_name' => 'web']);
        $newRole->syncPermissions($role->permissions->pluck('name')->toArray());

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role '{$newRole->name}' berhasil diduplikasi dari '{$role->name}'.");
    }

    public function applyPreset(Request $request, Role $role)
    {
        $presetKey = strtolower(trim($request->input('preset', $role->name)));
        $presets = \App\Support\PermissionRegistry::getDefaultRolePresets();

        // Fallback matching
        if (!isset($presets[$presetKey])) {
            foreach ($presets as $k => $v) {
                if (str_contains($presetKey, $k) || str_contains($k, $presetKey)) {
                    $presetKey = $k;
                    break;
                }
            }
        }

        if (!isset($presets[$presetKey])) {
            return redirect()->back()->with('error', "Template preset untuk '{$presetKey}' tidak ditemukan.");
        }

        $permissions = $presets[$presetKey];
        if ($role->name === 'admin') {
            $role->syncPermissions(\Spatie\Permission\Models\Permission::all());
        } else {
            $role->syncPermissions($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Template preset '{$presetKey}' berhasil diterapkan ke role '{$role->name}' (" . count($permissions) . " izin).");
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->back()->with('error', 'Role Admin tidak dapat dihapus.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', "Role '{$role->name}' masih digunakan oleh {$role->users()->count()} user. Pindahkan user terlebih dahulu.");
        }

        $role->delete();

        return redirect()->back()->with('success', "Role berhasil dihapus.");
    }
}
