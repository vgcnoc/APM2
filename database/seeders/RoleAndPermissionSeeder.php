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
            'dashboard_view',
            'customers_view', 'customers_create', 'customers_edit', 'customers_delete',
            'network_view', 'network_create', 'network_edit', 'network_delete',
            'ftth_view', 'ftth_create', 'ftth_edit', 'ftth_delete',
            'materials_view', 'materials_create', 'materials_edit', 'materials_delete',
            'roles_manage', 'users_manage', 'settings_manage'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo(Permission::all());

        $roleNoc = Role::firstOrCreate(['name' => 'noc']);
        $roleNoc->givePermissionTo([
            'dashboard_view', 'network_view', 'network_create', 'network_edit', 'network_delete',
            'ftth_view', 'ftth_create', 'ftth_edit', 'ftth_delete'
        ]);

        $roleTeknisi = Role::firstOrCreate(['name' => 'teknisi']);
        $roleTeknisi->givePermissionTo([
            'dashboard_view', 'network_view', 'ftth_view', 'materials_view'
        ]);

        $roleCs = Role::firstOrCreate(['name' => 'cs']);
        $roleCs->givePermissionTo([
            'dashboard_view', 'customers_view', 'customers_create', 'customers_edit'
        ]);

        $roleSales = Role::firstOrCreate(['name' => 'sales']);
        $roleSales->givePermissionTo([
            'dashboard_view', 'customers_view', 'customers_create'
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
