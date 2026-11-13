<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Samakan hak akses role 'teknisi-2' (akun Yahya Maulana) persis dengan role 'teknisi'
        $teknisiRole = Role::where('name', 'teknisi')->first();
        $teknisi2Role = Role::where('name', 'teknisi-2')->first();

        if ($teknisiRole && $teknisi2Role) {
            $teknisiPerms = $teknisiRole->permissions()->pluck('name')->toArray();
            $teknisi2Role->syncPermissions($teknisiPerms);
            $teknisi2Role->update(['permissions_customized_at' => now()]);
        }

        // 2. Cabut izin terkait ODC/ODP network_capacity dari role 'sales'
        $salesRole = Role::where('name', 'sales')->first();
        if ($salesRole) {
            $networkCapacityPerms = [
                'menu_network_capacity',
                'network_capacity_card_stats',
                'network_capacity_tab_odp',
                'network_capacity_tab_odc',
                'network_capacity_tab_expansion',
                'network_capacity_filter',
                'network_capacity_export',
                'network_capacity_btn_rollout',
                'network_capacity_view_area',
                'network_capacity_view_all',
            ];
            $salesRole->revokePermissionTo($networkCapacityPerms);
            $salesRole->update(['permissions_customized_at' => now()]);
        }

        // 3. Clear permission cache & refresh Inertia RBAC version
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            Cache::put('inertia_rbac_ver', time(), 86400);
        } catch (\Throwable $e) {
            //
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
