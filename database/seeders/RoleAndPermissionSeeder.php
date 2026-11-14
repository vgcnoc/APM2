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
        $allPermissionNames = \App\Support\PermissionRegistry::allNames();

        foreach ($allPermissionNames as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // 2. Sync all role presets
        // PENTING: Role 'admin' selalu mendapatkan seluruh permissions.
        // Role lain HANYA diinisialisasi jika baru dibuat atau belum memiliki izin sama sekali.
        // Izin yang sudah dikonfigurasi kustom oleh admin di database DIJAGA & TIDAK DITIMPA.
        $presets = \App\Support\PermissionRegistry::getDefaultRolePresets();
        foreach ($presets as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if ($roleName === 'admin') {
                $role->syncPermissions(Permission::all());
            } elseif ($role->wasRecentlyCreated) {
                // Hanya role yang benar-benar baru. Role lama (meski izinnya sengaja dikosongkan admin) tidak disentuh.
                $validPerms = Permission::whereIn('name', $perms)->pluck('name')->toArray();
                $role->syncPermissions($validPerms);
            }
        }

        // Role yang sudah dikunci admin (permissions_customized_at) tidak boleh ditambah izin otomatis.
        $rolesTable = config('permission.table_names.roles', 'roles');
        $unlocked = fn($q) => \Illuminate\Support\Facades\Schema::hasColumn($rolesTable, 'permissions_customized_at')
            ? $q->whereNull('permissions_customized_at')
            : $q;

        // Auto-grant menu_work_schedules to any role that already had menu_shifts
        $rolesWithShifts = $unlocked(Role::whereHas('permissions', fn($q) => $q->where('name', 'menu_shifts')))->get();
        foreach ($rolesWithShifts as $r) {
            $r->givePermissionTo(['menu_work_schedules', 'work_schedules_manage']);
        }

        // Auto-grant work_schedules_view_all to roles with hr_employees_view_all
        $rolesWithAll = $unlocked(Role::whereHas('permissions', fn($q) => $q->where('name', 'hr_employees_view_all')))->get();
        foreach ($rolesWithAll as $r) {
            $r->givePermissionTo('work_schedules_view_all');
        }

        // Auto-grant installed permissions to CS roles
        $csRoles = Role::whereIn('name', ['cs', 'CS', 'customer care', 'Customer Care'])->get();
        $installedPerms = [
            'menu_customers_installed',
            'customers_installed_view_all',
            'customers_installed_view_area',
            'customers_installed_card_stats',
            'customers_installed_filter',
            'customers_installed_assign',
            'customers_installed_whatsapp',
            'customers_installed_audit',
            'customers_installed_report',
            'customers_installed_edit',
            'customers_installed_cancel',
        ];
        foreach ($csRoles as $r) {
            $r->givePermissionTo($installedPerms);
        }

        // 3. Assign roles to users based on user->role if they don't have roles assigned
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
