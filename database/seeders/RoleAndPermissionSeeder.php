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
            // General CRUD Permissions
            'customers_create', 'customers_edit', 'customers_delete',
            'network_create', 'network_edit', 'network_delete',
            'ftth_create', 'ftth_edit', 'ftth_delete',
            'materials_create', 'materials_edit', 'materials_delete',
            
            // Menu Permissions
            'menu_dashboard',
            'menu_customers_booking', 'menu_customers_survey', 'menu_customers_installed', 
            'menu_customers_activation', 'menu_customers_active', 'menu_customers_all',
            'menu_ftth', 'menu_network_topology', 'menu_network_data',
            'menu_network_olt', 'menu_network_odc', 'menu_network_odp', 'menu_network_ont',
            'menu_materials', 'menu_material_transactions',
            'menu_hr_employees', 'menu_hr_positions',
            'menu_settings_areas', 'menu_settings_roles', 'menu_settings_branding', 'menu_settings_api',
            'menu_users', 'menu_internet_packages'
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
            'network_create', 'network_edit', 'network_delete',
            'ftth_create', 'ftth_edit', 'ftth_delete',
            'menu_customers_installed', 'menu_customers_activation'
        ]);

        $roleTeknisi = Role::firstOrCreate(['name' => 'teknisi']);
        $roleTeknisi->givePermissionTo([
            'menu_dashboard', 'menu_ftth', 'menu_materials', 'menu_material_transactions',
            'menu_customers_installed'
        ]);

        $roleCs = Role::firstOrCreate(['name' => 'cs']);
        $roleCs->givePermissionTo([
            'menu_dashboard', 'menu_customers_booking', 'menu_customers_survey', 
            'menu_customers_active', 'menu_customers_all',
            'customers_create', 'customers_edit'
        ]);

        $roleSales = Role::firstOrCreate(['name' => 'sales']);
        $roleSales->givePermissionTo([
            'menu_dashboard', 'menu_customers_booking', 'menu_customers_active',
            'customers_create'
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
