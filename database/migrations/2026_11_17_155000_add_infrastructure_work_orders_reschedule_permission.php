<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Cache;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Daftarkan permission 'infrastructure_work_orders_reschedule' dan centang hak aksesnya
     * HANYA untuk role: maintenance, maintenance-1, infrastruktur, noc, manager (dan admin).
     */
    public function up(): void
    {
        // 1. Buat permission reschedule jika belum ada
        $perm = Permission::firstOrCreate([
            'name' => 'infrastructure_work_orders_reschedule',
            'guard_name' => 'web',
        ]);

        // 2. Daftar role yang DIIZINKAN memiliki tombol & aksi reschedule
        $allowedRoles = [
            'admin', 'Admin',
            'manager', 'Manager',
            'maintenance', 'Maintenance',
            'maintenance-1', 'Maintenance-1',
            'infrastruktur', 'Infrastruktur',
            'noc', 'NOC',
        ];

        // 3. Khusus role Manager, berikan juga akses menu & view rollout agar bisa membuka dan memonitor
        $managerRolloutPerms = [
            'menu_infrastructure_work_orders',
            'menu_rollout_progress',
            'infra_wo_card_stats',
            'infra_wo_tab_info',
            'infra_wo_tab_materials',
            'infra_wo_tab_qc',
            'infra_wo_tab_photos',
            'infra_wo_tab_logs',
            'infra_wo_filter',
            'infrastructure_work_orders_view_all',
            'infrastructure_work_orders_reschedule',
        ];
        foreach (['manager', 'Manager'] as $mgrRoleName) {
            $mgrRole = Role::where('name', $mgrRoleName)->where('guard_name', 'web')->first();
            if ($mgrRole) {
                $mgrRole->givePermissionTo($managerRolloutPerms);
            }
        }

        // 4. Berikan izin reschedule ke seluruh allowed roles
        foreach ($allowedRoles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($perm);
            }
        }

        // 5. Pastikan semua role di luar allowedRoles dicabut izin reschedulenya
        $disallowedRoles = Role::whereNotIn('name', $allowedRoles)
            ->where('guard_name', 'web')
            ->get();

        foreach ($disallowedRoles as $dRole) {
            if ($dRole->hasPermissionTo('infrastructure_work_orders_reschedule')) {
                $dRole->revokePermissionTo('infrastructure_work_orders_reschedule');
            }
        }

        // 6. Reset permission cache Spatie & refresh version token
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::forget('spatie.permission.cache');
        try {
            SystemSetting::updateOrCreate(
                ['key' => 'permissions_cache_version'],
                ['value' => (string) now()->timestamp]
            );
        } catch (\Throwable $e) {
            // Ignore if settings table doesn't have permissions_cache_version
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = Permission::where('name', 'infrastructure_work_orders_reschedule')->first();
        if ($perm) {
            $perm->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::forget('spatie.permission.cache');
    }
};
