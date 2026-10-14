<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Register all permissions from master registry
        $registry = \App\Http\Controllers\RoleController::getPermissionRegistry();
        $allPermissionNames = [];

        foreach ($registry as $group) {
            foreach ($group['permissions'] as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm['name'],
                    'guard_name' => 'web',
                ]);
                $allPermissionNames[] = $perm['name'];
            }
        }

        // 2. Role Admin / Superadmin -> all permissions
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions(Permission::all());

        // 3. Role NOC
        $roleNoc = Role::firstOrCreate(['name' => 'noc', 'guard_name' => 'web']);
        $roleNoc->givePermissionTo([
            'menu_dashboard', 'dashboard_view_stats', 'dashboard_view_charts',
            // Network & Topologi
            'menu_network_topology', 'menu_network_data', 'menu_ftth', 'ftth_manage',
            'menu_network_olt', 'network_olt_create', 'network_olt_edit', 'network_olt_delete', 'network_olt_view_all',
            'menu_network_odc', 'network_odc_create', 'network_odc_edit', 'network_odc_delete', 'network_odc_view_all',
            'menu_network_odp', 'network_odp_create', 'network_odp_edit', 'network_odp_delete', 'network_odp_view_all',
            'menu_network_ont', 'network_ont_create', 'network_ont_edit', 'network_ont_delete', 'network_ont_view_all',
            'menu_network_find_onu',
            // Rollout ODC & ODP
            'menu_infrastructure_work_orders', 'infrastructure_work_orders_create', 'infrastructure_work_orders_edit',
            'infrastructure_work_orders_dispatch', 'infrastructure_work_orders_report', 'infrastructure_work_orders_approve',
            'infrastructure_work_orders_view_all',
            // Router & RADIUS
            'menu_routers', 'routers_create', 'routers_edit', 'routers_delete', 'routers_ping',
            'menu_radius_nas', 'radius_nas_create', 'radius_nas_edit', 'radius_nas_delete',
            'menu_radius_online_users', 'radius_online_users_disconnect',
            'menu_radius_auth_logs',
            // Pelanggan (Instalasi & Aktivasi)
            'menu_customers_installed', 'customers_installed_edit', 'customers_installed_report', 'customers_installed_view_all',
            'menu_customers_activation', 'customers_activation_activate', 'customers_activation_view_all',
            'menu_customers_active', 'customers_active_suspend', 'customers_active_view_all',
            'menu_customers_online',
            'menu_customers_all', 'customers_all_view_all',
            // Tickets & Gangguan
            'menu_tickets', 'tickets_create', 'tickets_edit', 'tickets_delete', 'tickets_assign', 'tickets_close', 'tickets_view_all',
            'menu_cbp', 'cbp_manage',
            'menu_pgk', 'pgk_manage',
            'menu_pgj', 'pgj_manage',
            'menu_pgs', 'pgs_manage',
        ]);

        // 4. Role Teknisi
        $roleTeknisi = Role::firstOrCreate(['name' => 'teknisi', 'guard_name' => 'web']);
        $roleTeknisi->givePermissionTo([
            'menu_dashboard',
            // Jaringan Lapangan
            'menu_ftth',
            'menu_network_odc', 'network_odc_view_area',
            'menu_network_odp', 'network_odp_view_area',
            // Rollout Pekerjaan
            'menu_infrastructure_work_orders', 'infrastructure_work_orders_report', 'infrastructure_work_orders_view_area',
            // Material Gudang
            'menu_materials', 'materials_view_area',
            'menu_material_transactions', 'material_transactions_create', 'material_transactions_view_area',
            // Pekerjaan Pelanggan
            'menu_customers_survey', 'customers_survey_report', 'customers_survey_view_area',
            'menu_customers_installed', 'customers_installed_report', 'customers_installed_edit', 'customers_installed_view_area',
            // Tiket Lapangan
            'menu_tickets', 'tickets_edit', 'tickets_close', 'tickets_view_area',
            'menu_cbp', 'cbp_manage',
            'menu_pgk', 'menu_pgj', 'menu_pgs',
            // Presensi Diri Sendiri
            'menu_attendances',
        ]);

        // 5. Role CS (Customer Service)
        $roleCs = Role::firstOrCreate(['name' => 'cs', 'guard_name' => 'web']);
        $roleCs->givePermissionTo([
            'menu_dashboard', 'dashboard_view_stats',
            // Pelanggan Flow
            'menu_customers_booking', 'customers_booking_create', 'customers_booking_edit', 'customers_booking_request_survey', 'customers_booking_whatsapp', 'customers_booking_view_all',
            'menu_customers_survey', 'customers_survey_assign', 'customers_survey_reschedule', 'customers_survey_view_all',
            'menu_customers_installed', 'customers_installed_assign', 'customers_installed_view_all',
            'menu_customers_activation', 'customers_activation_view_all',
            'menu_customers_active', 'customers_active_edit', 'customers_active_view_all',
            'menu_customers_online',
            'menu_customers_all', 'customers_all_create', 'customers_all_edit', 'customers_all_export', 'customers_all_view_all',
            'menu_monitoring_pelanggan', 'monitoring_pelanggan_manage',
            // Tiket & Gangguan
            'menu_tickets', 'tickets_create', 'tickets_edit', 'tickets_assign', 'tickets_close', 'tickets_view_all',
            'menu_pgk', 'pgk_manage',
            // Billing & Invoice Pelanggan
            'menu_invoices', 'invoices_create', 'invoices_edit', 'invoices_pay', 'invoices_promise', 'invoices_print', 'invoices_view_all',
            'menu_customer_billing', 'customer_billing_collect',
            // Info Paket Internet
            'menu_internet_packages',
        ]);

        // 6. Role Sales
        $roleSales = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $roleSales->givePermissionTo([
            'menu_dashboard',
            'menu_customers_booking', 'customers_booking_create', 'customers_booking_edit', 'customers_booking_whatsapp', 'customers_booking_view_area',
            'menu_customers_survey', 'customers_survey_view_area',
            'menu_customers_active', 'customers_active_view_area',
            'menu_customers_all', 'customers_all_create', 'customers_all_view_area',
            'menu_marketing_affiliates',
            'menu_tickets', 'tickets_create',
            'menu_internet_packages',
        ]);

        // 7. Role Reseller
        $roleReseller = Role::firstOrCreate(['name' => 'reseller', 'guard_name' => 'web']);
        $roleReseller->givePermissionTo([
            'menu_dashboard',
            'menu_vouchers', 'vouchers_create', 'vouchers_print',
        ]);

        // 8. Assign roles to users based on user->role if they don't have roles assigned
        $users = User::all();
        foreach ($users as $user) {
            if ($user->roles()->count() === 0) {
                if ($user->role && Role::where('name', $user->role)->exists()) {
                    $user->assignRole($user->role);
                } else {
                    $user->assignRole('admin');
                }
            }
        }

        // Final cache clear
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
