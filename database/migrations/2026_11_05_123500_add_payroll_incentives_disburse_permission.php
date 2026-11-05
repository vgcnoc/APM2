<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Buat permission payroll_incentives_disburse
        $perm = Permission::firstOrCreate([
            'name' => 'payroll_incentives_disburse',
            'guard_name' => 'web',
        ]);

        // 2. Berikan ke role admin
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole && !$adminRole->hasPermissionTo($perm)) {
            $adminRole->givePermissionTo($perm);
        }

        // 3. Berikan ke role hrd
        $hrdRole = Role::where('name', 'hrd')->where('guard_name', 'web')->first();
        if ($hrdRole && !$hrdRole->hasPermissionTo($perm)) {
            $hrdRole->givePermissionTo($perm);
        }

        // 4. Berikan ke role finance
        $financeRole = Role::where('name', 'finance')->where('guard_name', 'web')->first();
        if ($financeRole && !$financeRole->hasPermissionTo($perm)) {
            $financeRole->givePermissionTo($perm);
        }

        // Reset cache again
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = Permission::where('name', 'payroll_incentives_disburse')->where('guard_name', 'web')->first();
        if ($perm) {
            $perm->delete();
        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

