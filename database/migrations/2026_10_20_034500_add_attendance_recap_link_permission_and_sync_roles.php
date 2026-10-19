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
        // 1. Reset permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Daftarkan seluruh permissions dari PermissionRegistry (termasuk attendance_recap_link)
        $allNames = PermissionRegistry::allNames();
        foreach ($allNames as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // 3. Pastikan role admin memiliki seluruh permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        // 4. Sinkronisasikan preset role standar
        $presets = PermissionRegistry::getDefaultRolePresets();
        foreach ($presets as $roleName => $perms) {
            if ($roleName === 'admin') continue;

            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $validPerms = Permission::whereIn('name', $perms)->pluck('name')->toArray();
            $role->givePermissionTo($validPerms);
        }

        // 5. Reset cache kembali
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
