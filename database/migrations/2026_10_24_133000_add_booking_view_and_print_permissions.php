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

        // 1. Pastikan permission baru dibuat di database
        $newPerms = [
            'customers_booking_view',
            'customers_booking_print',
        ];

        foreach ($newPerms as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // 2. Berikan ke admin
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($newPerms);

        // 3. Sinkronkan ke role presets standar
        $presets = PermissionRegistry::getDefaultRolePresets();
        foreach ($presets as $roleName => $perms) {
            if ($roleName === 'admin') continue;

            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $toGive = array_intersect($newPerms, $perms);
                if (!empty($toGive)) {
                    $role->givePermissionTo($toGive);
                }
            }
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
