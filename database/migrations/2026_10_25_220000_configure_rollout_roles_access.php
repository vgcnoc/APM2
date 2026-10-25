<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
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

        // 1. Pastikan seluruh izin Rollout Work Orders terdaftar di database
        $allRolloutPerms = [
            'menu_infrastructure_work_orders',
            'infra_wo_card_stats',
            'infra_wo_tab_info',
            'infra_wo_tab_materials',
            'infra_wo_tab_qc',
            'infra_wo_tab_photos',
            'infra_wo_tab_logs',
            'infra_wo_filter',
            'infrastructure_work_orders_create',
            'infrastructure_work_orders_edit',
            'infrastructure_work_orders_delete',
            'infrastructure_work_orders_dispatch',
            'infra_wo_btn_print_sj',
            'infrastructure_work_orders_review_material',
            'infrastructure_work_orders_start',
            'infrastructure_work_orders_request_material',
            'infrastructure_work_orders_report',
            'infrastructure_work_orders_upload_photo',
            'infrastructure_work_orders_approve',
            'infrastructure_work_orders_reject',
            'infrastructure_work_orders_view_all',
            'infrastructure_work_orders_view_area',
            'infrastructure_work_orders_view_assigned',
        ];

        foreach ($allRolloutPerms as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Berikan seluruh izin ke admin
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleAdmin->givePermissionTo($allRolloutPerms);

        // 3. Hak Akses Penugasan Rollout ODC & ODP (Maintenance & Maintenance-1)
        // Fungsi: Menerbitkan WO baru, edit parameter, hapus, setujui material gudang, cetak SJ, review material tambahan, validasi QC redaman & approve/reject ke GIS
        $assignerPerms = [
            'menu_infrastructure_work_orders',
            'infra_wo_card_stats',
            'infra_wo_tab_info',
            'infra_wo_tab_materials',
            'infra_wo_tab_qc',
            'infra_wo_tab_photos',
            'infra_wo_tab_logs',
            'infra_wo_filter',
            'infrastructure_work_orders_create',
            'infrastructure_work_orders_edit',
            'infrastructure_work_orders_delete',
            'infrastructure_work_orders_dispatch',
            'infra_wo_btn_print_sj',
            'infrastructure_work_orders_review_material',
            'infrastructure_work_orders_upload_photo',
            'infrastructure_work_orders_approve',
            'infrastructure_work_orders_reject',
            'infrastructure_work_orders_view_area',
            'infrastructure_work_orders_view_all',
        ];

        $workerOnlyPerms = [
            'infrastructure_work_orders_start',
            'infrastructure_work_orders_request_material',
            'infrastructure_work_orders_report',
        ];

        foreach (['maintenance', 'maintenance-1'] as $mRoleName) {
            $mRole = Role::firstOrCreate(['name' => $mRoleName, 'guard_name' => 'web']);
            $mRole->givePermissionTo($assignerPerms);
            // Cabut hak pengerjaan lapangan murni agar fokus sebagai penugasan & supervisor
            foreach ($workerOnlyPerms as $wp) {
                if ($mRole->hasPermissionTo($wp)) {
                    $mRole->revokePermissionTo($wp);
                }
            }
        }

        // 4. Hak Akses Pelaksana Rollout ODC & ODP (Teknisi Tim Serbu, Tim Serbu, Teknisi, Infrastruktur)
        // Fungsi: Menangani penugasan yang diberikan, cetak surat jalan, mulai kerja, ajukan request material, submit laporan lapangan & QC redaman OPM, upload foto fisik
        $workerPerms = [
            'menu_infrastructure_work_orders',
            'infra_wo_card_stats',
            'infra_wo_tab_info',
            'infra_wo_tab_materials',
            'infra_wo_tab_qc',
            'infra_wo_tab_photos',
            'infra_wo_tab_logs',
            'infra_wo_filter',
            'infra_wo_btn_print_sj',
            'infrastructure_work_orders_start',
            'infrastructure_work_orders_request_material',
            'infrastructure_work_orders_report',
            'infrastructure_work_orders_upload_photo',
            'infrastructure_work_orders_view_assigned',
            'infrastructure_work_orders_view_area',
        ];

        $assignerOnlyPerms = [
            'infrastructure_work_orders_create',
            'infrastructure_work_orders_edit',
            'infrastructure_work_orders_delete',
            'infrastructure_work_orders_dispatch',
            'infrastructure_work_orders_review_material',
            'infrastructure_work_orders_approve',
            'infrastructure_work_orders_reject',
            'infrastructure_work_orders_view_all',
        ];

        $workerRoleNames = ['teknisi tim serbu', 'tim serbu', 'teknisi', 'infrastruktur'];
        $presets = PermissionRegistry::getDefaultRolePresets();

        foreach ($workerRoleNames as $wRoleName) {
            $wRole = Role::firstOrCreate(['name' => $wRoleName, 'guard_name' => 'web']);
            
            // Jika role baru atau permissions sedikit (seperti tim serbu yang 0), inisialisasi dari preset
            if ($wRole->permissions()->count() <= 15 && isset($presets[$wRoleName])) {
                $validInitial = Permission::whereIn('name', $presets[$wRoleName])->pluck('name')->toArray();
                $wRole->syncPermissions($validInitial);
            }

            // Pastikan izin penanganan rollout terpasang
            $wRole->givePermissionTo($workerPerms);

            // Cabut hak penugasan/approval agar tidak bisa mengubah status penugasan secara sepihak
            foreach ($assignerOnlyPerms as $ap) {
                if ($wRole->hasPermissionTo($ap)) {
                    $wRole->revokePermissionTo($ap);
                }
            }
        }

        // 5. Sinkronkan relasi Spatie User Role ke akun-akun terkait
        $roleMaintenance = Role::where('name', 'maintenance')->first();
        $roleMaintenance1 = Role::where('name', 'maintenance-1')->first();
        $roleTimSerbu = Role::where('name', 'teknisi tim serbu')->first();

        if ($roleMaintenance) {
            $mUsers = User::where('role', 'maintenance')->get();
            foreach ($mUsers as $u) {
                if (!$u->hasRole('maintenance')) {
                    $u->assignRole($roleMaintenance);
                }
            }
        }

        if ($roleMaintenance1) {
            $m1Users = User::where('role', 'maintenance-1')->get();
            foreach ($m1Users as $u) {
                if (!$u->hasRole('maintenance-1')) {
                    $u->assignRole($roleMaintenance1);
                }
            }
        }

        if ($roleTimSerbu) {
            $serbuUsers = User::where('role', 'teknisi tim serbu')
                ->orWhereHas('employee', function ($eq) {
                    $eq->where('position', 'like', '%tim serbu%');
                })
                ->get();

            foreach ($serbuUsers as $u) {
                if (!$u->hasRole('teknisi tim serbu')) {
                    $u->assignRole($roleTimSerbu);
                }
            }
        }

        // 6. Reset permission cache
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
