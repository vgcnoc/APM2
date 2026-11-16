<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Batasi hak akses 'Progress Rollout Pembangunan ODC & ODP' hanya tercentang pada
     * role: maintenance, maintenance-1, infrastruktur, noc (dan superadmin admin).
     */
    public function up(): void
    {
        // 1. Pastikan seluruh permissions rollout terdaftar di database
        $rolloutPermissions = [
            'menu_infrastructure_work_orders',
            'menu_rollout_progress',
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

        foreach ($rolloutPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Daftar role yang DIIZINKAN memiliki akses Progress Rollout
        $allowedRoles = ['admin', 'noc', 'maintenance', 'maintenance-1', 'infrastruktur'];

        // 3. Berikan izin rollout ke role yang diizinkan
        // NOC: Full QA, Approval, Monitoring & Dispatch
        $nocPerms = [
            'menu_infrastructure_work_orders', 'menu_rollout_progress', 'infra_wo_card_stats',
            'infra_wo_tab_info', 'infra_wo_tab_materials', 'infra_wo_tab_qc', 'infra_wo_tab_photos',
            'infra_wo_tab_logs', 'infra_wo_filter', 'infrastructure_work_orders_create',
            'infrastructure_work_orders_edit', 'infrastructure_work_orders_delete',
            'infrastructure_work_orders_dispatch', 'infra_wo_btn_print_sj',
            'infrastructure_work_orders_review_material', 'infrastructure_work_orders_start',
            'infrastructure_work_orders_request_material', 'infrastructure_work_orders_report',
            'infrastructure_work_orders_upload_photo', 'infrastructure_work_orders_approve',
            'infrastructure_work_orders_reject', 'infrastructure_work_orders_view_all'
        ];
        foreach (['noc', 'NOC'] as $rName) {
            $r = Role::where('name', $rName)->where('guard_name', 'web')->first();
            if ($r) $r->givePermissionTo($nocPerms);
        }

        // Maintenance & Maintenance-1: Persiapan Rute, Penugasan, Eksekusi & QC
        $maintPerms = [
            'menu_infrastructure_work_orders', 'menu_rollout_progress', 'infra_wo_card_stats',
            'infra_wo_tab_info', 'infra_wo_tab_materials', 'infra_wo_tab_qc', 'infra_wo_tab_photos',
            'infra_wo_tab_logs', 'infra_wo_filter', 'infrastructure_work_orders_create',
            'infrastructure_work_orders_edit', 'infrastructure_work_orders_delete',
            'infrastructure_work_orders_dispatch', 'infra_wo_btn_print_sj',
            'infrastructure_work_orders_review_material', 'infrastructure_work_orders_upload_photo',
            'infrastructure_work_orders_approve', 'infrastructure_work_orders_reject',
            'infrastructure_work_orders_view_area', 'infrastructure_work_orders_view_all'
        ];
        foreach (['maintenance', 'Maintenance', 'maintenance-1', 'Maintenance-1'] as $rName) {
            $r = Role::where('name', $rName)->where('guard_name', 'web')->first();
            if ($r) $r->givePermissionTo($maintPerms);
        }

        // Infrastruktur: Eksekusi Lapangan, Penarikan Kabel, Splicing & Submit QC Redaman
        $infraPerms = [
            'menu_infrastructure_work_orders', 'menu_rollout_progress', 'infra_wo_card_stats',
            'infra_wo_tab_info', 'infra_wo_tab_materials', 'infra_wo_tab_qc', 'infra_wo_tab_photos',
            'infra_wo_tab_logs', 'infra_wo_filter', 'infra_wo_btn_print_sj',
            'infrastructure_work_orders_create', 'infrastructure_work_orders_edit',
            'infrastructure_work_orders_start', 'infrastructure_work_orders_request_material',
            'infrastructure_work_orders_report', 'infrastructure_work_orders_upload_photo',
            'infrastructure_work_orders_view_assigned', 'infrastructure_work_orders_view_area',
            'infrastructure_work_orders_view_all'
        ];
        foreach (['infrastruktur', 'Infrastruktur'] as $rName) {
            $r = Role::where('name', $rName)->where('guard_name', 'web')->first();
            if ($r) $r->givePermissionTo($infraPerms);
        }

        // Admin: Full Access
        $roleAdmin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($roleAdmin) {
            $roleAdmin->givePermissionTo($rolloutPermissions);
        }

        // 4. Cabut SELURUH izin rollout dari role lainnya (Dispatcher, Gudang, Kepala Teknisi, Manager, Tim Serbu, dll)
        $allRoles = Role::where('guard_name', 'web')->get();
        foreach ($allRoles as $role) {
            $roleLower = strtolower(trim($role->name));
            if (!in_array($roleLower, $allowedRoles)) {
                $role->revokePermissionTo($rolloutPermissions);
            }
        }

        // 5. Bersihkan cache permissions Spatie & RBAC
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::put('inertia_rbac_ver', time(), 86400);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback safe
    }
};

