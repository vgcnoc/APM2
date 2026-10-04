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

        // create permissions
        $permissions = [
            // Menu Dashboard
            'menu_dashboard',
            
            // Menu Customers
            'menu_customers_booking', 'customers_booking_create', 'customers_booking_edit', 'customers_booking_delete',
            'menu_customers_survey', 'customers_survey_create', 'customers_survey_edit', 'customers_survey_delete', 'customers_survey_report',
            'menu_customers_installed', 'customers_installed_create', 'customers_installed_edit', 'customers_installed_delete', 'customers_installed_report',
            'menu_customers_activation', 'customers_activation_create', 'customers_activation_edit', 'customers_activation_delete',
            'menu_customers_active', 'customers_active_create', 'customers_active_edit', 'customers_active_delete',
            'menu_customers_all', 'customers_all_create', 'customers_all_edit', 'customers_all_delete',
            
            // Menu Network
            'menu_ftth', 'ftth_create', 'ftth_edit', 'ftth_delete',
            'menu_network_topology', 'network_topology_create', 'network_topology_edit', 'network_topology_delete',
            'menu_network_data', 'network_data_create', 'network_data_edit', 'network_data_delete',
            'menu_network_olt', 'network_olt_create', 'network_olt_edit', 'network_olt_delete',
            'menu_network_odc', 'network_odc_create', 'network_odc_edit', 'network_odc_delete',
            'menu_network_odp', 'network_odp_create', 'network_odp_edit', 'network_odp_delete',
            'menu_network_ont', 'network_ont_create', 'network_ont_edit', 'network_ont_delete',
            
            // Menu Materials
            'menu_materials', 'materials_create', 'materials_edit', 'materials_delete',
            'menu_material_transactions', 'material_transactions_create', 'material_transactions_edit', 'material_transactions_delete',
            
            // Menu Settings / HR
            'menu_hr_employees', 'hr_employees_create', 'hr_employees_edit', 'hr_employees_delete',
            'menu_hr_positions', 'hr_positions_create', 'hr_positions_edit', 'hr_positions_delete',
            'menu_settings_areas', 'settings_areas_create', 'settings_areas_edit', 'settings_areas_delete',
            'menu_settings_roles', 'settings_roles_create', 'settings_roles_edit', 'settings_roles_delete',
            'menu_settings_branding', 'settings_branding_create', 'settings_branding_edit', 'settings_branding_delete',
            'menu_settings_api', 'settings_api_create', 'settings_api_edit', 'settings_api_delete',
            'menu_users', 'users_create', 'users_edit', 'users_delete',
            'menu_internet_packages', 'internet_packages_create', 'internet_packages_edit', 'internet_packages_delete',
            
            // Menu Vouchers & Resellers
            'menu_vouchers', 'vouchers_create', 'vouchers_edit', 'vouchers_delete', 'vouchers_print',
            'menu_resellers', 'resellers_create', 'resellers_edit', 'resellers_delete'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo(Permission::all());

        $roleNoc = Role::firstOrCreate(['name' => 'noc']);
        $roleNoc->givePermissionTo([
            'menu_dashboard', 'menu_ftth', 'menu_network_topology', 'menu_network_data',
            'menu_network_olt', 'menu_network_odc', 'menu_network_odp', 'menu_network_ont',
            'menu_customers_installed', 'menu_customers_activation'
        ]);

        $roleTeknisi = Role::firstOrCreate(['name' => 'teknisi']);
        $roleTeknisi->givePermissionTo([
            'menu_dashboard', 'menu_ftth', 'menu_materials', 'menu_material_transactions',
            'menu_customers_installed', 'customers_installed_report', 'customers_installed_edit'
        ]);

        $roleCs = Role::firstOrCreate(['name' => 'cs']);
        $roleCs->givePermissionTo([
            'menu_dashboard', 'menu_customers_booking', 'menu_customers_survey', 
            'menu_customers_active', 'menu_customers_all',
            'customers_booking_create', 'customers_booking_edit',
            'customers_survey_create', 'customers_survey_edit',
            'customers_active_create', 'customers_active_edit',
            'customers_all_create', 'customers_all_edit'
        ]);

        $roleSales = Role::firstOrCreate(['name' => 'sales']);
        $roleSales->givePermissionTo([
            'menu_dashboard', 'menu_customers_booking', 'menu_customers_active',
            'customers_booking_create'
        ]);

        // assign role to existing users based on their 'role' column
        $users = User::all();
        foreach ($users as $user) {
            if ($user->role && Role::where('name', $user->role)->exists()) {
                $user->assignRole($user->role);
            } else {
                $user->assignRole('admin'); // fallback to admin so no one gets locked out
            }
        }
    }
}
