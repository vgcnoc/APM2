<?php

use App\Models\Employee;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $chronicPermissions = [
            'menu_chronic_monitoring',
            'chronic_monitoring_card_stats',
            'chronic_monitoring_tab_customers',
            'chronic_monitoring_tab_paths',
            'chronic_monitoring_tab_visits',
            'chronic_monitoring_filter',
            'chronic_socialization_manage',
            'chronic_socialization_report',
            'chronic_socialization_delete',
            'chronic_monitoring_schedule_maintenance',
            'chronic_monitoring_whatsapp',
            'chronic_monitoring_view_all',
            'chronic_monitoring_view_area',
        ];

        // 1. Ensure all permissions from PermissionRegistry exist in DB
        foreach (PermissionRegistry::allNames() as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        $presets = PermissionRegistry::getDefaultRolePresets();

        // 2. Ensure the target roles exist and are initialized if newly created / empty
        $targetRolesConfig = [
            'maintenance' => 'area',
            'maintenance-1' => 'area',
            'manager' => 'all',
            'noc' => 'all',
            'cs' => 'all',
            'infrastruktur' => 'area',
        ];

        foreach ($targetRolesConfig as $roleName => $scopeMode) {
            $existing = Role::whereRaw('LOWER(name) = ?', [strtolower($roleName)])
                ->where('guard_name', 'web')
                ->first();

            if (!$existing) {
                $existing = Role::create([
                    'name' => $roleName,
                    'guard_name' => 'web',
                ]);
            }

            // If this role currently has 0 permissions, seed its full preset
            if ($existing->permissions()->count() === 0 && isset($presets[$roleName])) {
                $existing->syncPermissions($presets[$roleName]);
            }
        }

        // 3. Strictly configure Chronic Monitoring permissions across ALL roles in DB
        $allowedRoleNamesLower = [
            'admin',
            'super admin',
            'maintenance',
            'maintenance-1',
            'manager',
            'noc',
            'cs',
            'infrastruktur',
        ];

        $allAreaRolesLower = ['admin', 'super admin', 'manager', 'noc', 'cs'];

        $baseChronicPerms = [
            'menu_chronic_monitoring',
            'chronic_monitoring_card_stats',
            'chronic_monitoring_tab_customers',
            'chronic_monitoring_tab_paths',
            'chronic_monitoring_tab_visits',
            'chronic_monitoring_filter',
            'chronic_socialization_manage',
            'chronic_socialization_report',
            'chronic_socialization_delete',
            'chronic_monitoring_schedule_maintenance',
            'chronic_monitoring_whatsapp',
        ];

        $allRoles = Role::where('guard_name', 'web')->get();

        foreach ($allRoles as $role) {
            $rLower = mb_strtolower(trim($role->name));

            if (in_array($rLower, $allowedRoleNamesLower, true)) {
                $permsToGrant = $baseChronicPerms;
                if (in_array($rLower, $allAreaRolesLower, true)) {
                    $permsToGrant[] = 'chronic_monitoring_view_all';
                    $role->revokePermissionTo('chronic_monitoring_view_area');
                } else {
                    $permsToGrant[] = 'chronic_monitoring_view_area';
                    $role->revokePermissionTo('chronic_monitoring_view_all');
                }

                // Ensure Manager/allowed roles can also submit schedule maintenance to NetworkMaintenanceController
                if ($rLower === 'manager') {
                    $permsToGrant[] = 'menu_network_maintenance';
                    $permsToGrant[] = 'network_maintenance_create';
                    $permsToGrant[] = 'network_maintenance_update';
                }

                $role->givePermissionTo($permsToGrant);
            } else {
                // Explicitly revoke all chronic monitoring permissions from all other roles
                $role->revokePermissionTo($chronicPermissions);
            }
        }

        // 4. Sync Spatie role for users whose `users.role` or `employees.position` matches one of the 6 target roles
        if (Schema::hasTable('users')) {
            $employeesByName = Schema::hasTable('employees')
                ? Employee::select('id', 'name', 'position')->get()->keyBy(fn ($e) => mb_strtolower(trim((string) $e->name)))
                : collect();

            $users = User::with('roles')->get();
            foreach ($users as $u) {
                $uRoleLower = mb_strtolower(trim((string) $u->role));
                $emp = $employeesByName->get(mb_strtolower(trim((string) $u->name)));
                $empPosLower = $emp ? mb_strtolower(trim((string) $emp->position)) : '';

                // If user.role or employee.position is explicitly one of our target roles (e.g. infrastruktur, maintenance-1)
                foreach (array_keys($targetRolesConfig) as $tRole) {
                    if ($uRoleLower === $tRole || $empPosLower === $tRole) {
                        $spatieRole = Role::whereRaw('LOWER(name) = ?', [$tRole])->where('guard_name', 'web')->first();
                        if ($spatieRole && !$u->hasRole($spatieRole->name)) {
                            $u->syncRoles([$spatieRole->name]);
                        }
                        break;
                    }
                }
            }
        }

        // 5. Remove any stray direct model_has_permissions for chronicPermissions on non-allowed users
        if (Schema::hasTable('model_has_permissions')) {
            $chronicPermIds = Permission::whereIn('name', $chronicPermissions)->pluck('id')->toArray();
            if (!empty($chronicPermIds)) {
                DB::table('model_has_permissions')
                    ->whereIn('permission_id', $chronicPermIds)
                    ->delete();
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::put('inertia_rbac_ver', time(), 86400);
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::put('inertia_rbac_ver', time(), 86400);
    }
};

