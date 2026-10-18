<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Support\PermissionRegistry;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Register all permissions from master registry
        $allPermissionNames = PermissionRegistry::allNames();
        foreach ($allPermissionNames as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // 3. Sync default role presets to ensure technicians only get assigned scope and granular actions
        $presets = PermissionRegistry::getDefaultRolePresets();
        foreach ($presets as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if ($roleName === 'admin') {
                $role->syncPermissions(Permission::all());
            } else {
                $validPerms = Permission::whereIn('name', $perms)->pluck('name')->toArray();
                $role->syncPermissions($validPerms);
            }
        }

        // 4. Final cache clear
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
