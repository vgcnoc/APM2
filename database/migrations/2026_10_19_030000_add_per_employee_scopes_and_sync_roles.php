<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Support\PermissionRegistry;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Buat seluruh permission baru yang belum ada di database
        $allNames = PermissionRegistry::allNames();
        foreach ($allNames as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // 2. Berikan semua permissions ke role 'admin'
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        // 3. Lengkapi izin untuk preset role standar (teknisi, sales, maintenance, dll)
        $presets = PermissionRegistry::getDefaultRolePresets();
        foreach ($presets as $roleName => $perms) {
            if ($roleName === 'admin') continue;

            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $validPerms = Permission::whereIn('name', $perms)->pluck('name')->toArray();
            $role->givePermissionTo($validPerms);
        }

        // Reset cache kembali
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
