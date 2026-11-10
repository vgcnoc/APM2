<?php

use App\Support\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
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

        // 1. Pastikan seluruh permission dari PermissionRegistry terdaftar di DB
        foreach (PermissionRegistry::allNames() as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        // 2. Role yang HANYA diizinkan mengakses Bagi Hasil & Kemitraan:
        //    - noc
        //    - finance & hrd (finance, hrd, hrd & finance, hrd & keuangan)
        //    - administrator (admin, super admin)
        $allowedRolesForProfitSharing = [
            'admin',
            'super admin',
            'administrator',
            'noc',
            'finance',
            'hrd & finance',
            'hrd & keuangan',
            'hrd',
        ];

        $profitSharingPermissions = ['menu_profit_sharing', 'profit_sharing_manage'];

        $roles = Role::all();
        foreach ($roles as $role) {
            $normalizedRole = strtolower(trim($role->name));

            $isAllowed = false;
            foreach ($allowedRolesForProfitSharing as $allowed) {
                if ($normalizedRole === $allowed || str_contains($normalizedRole, $allowed)) {
                    $isAllowed = true;
                    break;
                }
            }

            if ($isAllowed) {
                // Berikan hak akses Bagi Hasil & Kemitraan
                $role->givePermissionTo($profitSharingPermissions);
            } else {
                // Pastikan role lain TIDAK dicentang untuk Bagi Hasil & Kemitraan
                foreach ($profitSharingPermissions as $perm) {
                    if ($role->hasPermissionTo($perm)) {
                        $role->revokePermissionTo($perm);
                    }
                }

                // Cabut juga menu_financial_reports dari akun selain admin & finance/hrd
                if ($role->hasPermissionTo('menu_financial_reports')) {
                    $role->revokePermissionTo('menu_financial_reports');
                }
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Safe rollback
    }
};

