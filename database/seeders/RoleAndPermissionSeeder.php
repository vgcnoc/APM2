<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Register all permissions from master registry
        $allPermissionNames = \App\Support\PermissionRegistry::allNames();

        foreach ($allPermissionNames as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // 2. Sync all role presets
        $presets = \App\Support\PermissionRegistry::getDefaultRolePresets();
        foreach ($presets as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if ($roleName === 'admin') {
                $role->syncPermissions(Permission::all());
            } else {
                $validPerms = Permission::whereIn('name', $perms)->pluck('name')->toArray();
                $role->syncPermissions($validPerms);
            }
        }

        // 3. Assign roles to users based on user->role if they don't have roles assigned
        $users = User::all();
        foreach ($users as $user) {
            if ($user->roles()->count() === 0) {
                if ($user->role && Role::where('name', $user->role)->exists()) {
                    $user->assignRole($user->role);
                } else {
                    $user->assignRole('admin');
                }
            }
        }

        // Final cache clear
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
