<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reset permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Pastikan permission invoices_card_summary dan invoices_card_revenue terdaftar
        $permissions = [
            'invoices_card_summary',
            'invoices_card_revenue',
            'menu_invoices',
            'invoices_tab_all',
            'invoices_tab_unpaid',
            'invoices_tab_paid',
            'invoices_filter_search',
            'invoices_filter_status',
            'invoices_filter_period',
            'invoices_filter_area',
            'invoices_view_all',
            'invoices_print',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        // 3. Pastikan role admin memiliki seluruh izin
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(['invoices_card_summary', 'invoices_card_revenue']);
        }

        // 4. Role CS & Admin CS: Cabut invoices_card_summary & invoices_card_revenue
        $csRoles = Role::whereIn('name', ['cs', 'admin cs'])->where('guard_name', 'web')->get();
        foreach ($csRoles as $role) {
            if ($role->hasPermissionTo('invoices_card_summary')) {
                $role->revokePermissionTo('invoices_card_summary');
            }
            if ($role->hasPermissionTo('invoices_card_revenue')) {
                $role->revokePermissionTo('invoices_card_revenue');
            }
        }

        // 5. Role HRD: Berikan invoices_card_summary, invoices_card_revenue, menu_invoices, dll.
        $hrdRole = Role::where('name', 'hrd')->where('guard_name', 'web')->first();
        if ($hrdRole) {
            $hrdRole->givePermissionTo([
                'menu_invoices',
                'invoices_card_summary',
                'invoices_card_revenue',
                'invoices_tab_all',
                'invoices_tab_unpaid',
                'invoices_tab_paid',
                'invoices_filter_search',
                'invoices_filter_status',
                'invoices_filter_period',
                'invoices_filter_area',
                'invoices_view_all',
                'invoices_print',
            ]);
        }

        // 6. Role Finance: Pastikan memiliki invoices_card_summary & invoices_card_revenue
        $financeRole = Role::where('name', 'finance')->where('guard_name', 'web')->first();
        if ($financeRole) {
            $financeRole->givePermissionTo([
                'menu_invoices',
                'invoices_card_summary',
                'invoices_card_revenue',
            ]);
        }

        // 7. Bersihkan cache Spatie dan Inertia RBAC
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::put('inertia_rbac_ver', time(), 86400);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
