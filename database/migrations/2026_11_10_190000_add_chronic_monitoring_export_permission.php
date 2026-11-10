<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate([
            'name' => 'chronic_monitoring_export',
            'guard_name' => 'web',
        ]);

        $allowedRoleNamesLower = [
            'admin',
            'super admin',
            'maintenance',
            'maintenance-1',
            'manager',
            'noc',
            'cs',
            'infrastruktur',
        ];

        $allRoles = Role::where('guard_name', 'web')->get();
        foreach ($allRoles as $role) {
            $rLower = mb_strtolower(trim($role->name));
            if (in_array($rLower, $allowedRoleNamesLower, true)) {
                $role->givePermissionTo('chronic_monitoring_export');
            } else {
                $role->revokePermissionTo('chronic_monitoring_export');
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::put('inertia_rbac_ver', time(), 86400);
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::put('inertia_rbac_ver', time(), 86400);
    }
};
