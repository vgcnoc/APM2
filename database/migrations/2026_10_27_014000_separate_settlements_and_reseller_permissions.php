<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reset permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Daftar permissions baru yang dipisah
        $newPermissions = [
            'menu_customer_settlements',
            'customer_settlements_cancel',
            'menu_reseller_billing',
            'menu_reseller_requests',
            'menu_reseller_settlements',
            'menu_reseller_reports',
        ];

        foreach ($newPermissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        // 3. Pastikan role admin memiliki permissions baru
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($newPermissions);

        // 4. Update role finance jika ada
        $financeRole = Role::where('name', 'finance')->where('guard_name', 'web')->first();
        if ($financeRole) {
            $financeRole->givePermissionTo($newPermissions);
        }

        // 5. Pastikan role sales dan kolektor TIDAK memiliki hak validasi setoran (agar terpisah dan tidak otomatis muncul)
        $collectorRoles = Role::whereIn('name', ['sales', 'kolektor', 'kolektor & sales'])->where('guard_name', 'web')->get();
        foreach ($collectorRoles as $cRole) {
            // Revoke settlement permission dari kolektor/sales jika sebelumnya sempat terpasang
            if ($cRole->hasPermissionTo('menu_customer_settlements')) {
                $cRole->revokePermissionTo('menu_customer_settlements');
            }
            if ($cRole->hasPermissionTo('customer_settlements_approve')) {
                $cRole->revokePermissionTo('customer_settlements_approve');
            }
            if ($cRole->hasPermissionTo('customer_settlements_cancel')) {
                $cRole->revokePermissionTo('customer_settlements_cancel');
            }

            // Pastikan terima setoran lapangan tetap aktif
            if (!$cRole->hasPermissionTo('menu_customer_billing')) {
                $cRole->givePermissionTo('menu_customer_billing');
            }
            if (!$cRole->hasPermissionTo('customer_billing_collect')) {
                $cRole->givePermissionTo('customer_billing_collect');
            }
        }

        // 6. Reset cache kembali
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
