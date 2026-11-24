<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public static array $holdingOnlyPermissions = [
        'menu_data_migration',
        'data_migration_template_download',
        'data_migration_dry_run',
        'data_migration_execute',
        'menu_data_masking',
    ];

    /**
     * Master list of all permissions grouped by module.
     * Categorized by 5 types: menu, card, tab, filter, button, feature.
     */
    public static function getPermissionRegistry(bool $isMitra = false): array
    {
        $groups = \App\Support\PermissionRegistry::get();
        if ($isMitra) {
            $holdingOnlyGroups = ['Data Migration Center', 'Privasi & Target Omset'];
            $holdingOnlyPermissions = self::$holdingOnlyPermissions;

            $groups = array_values(array_filter($groups, function ($group) use ($holdingOnlyGroups) {
                if (in_array($group['group'] ?? '', $holdingOnlyGroups, true)) {
                    return false;
                }
                if (in_array($group['menu_parent'] ?? '', ['menu_data_migration', 'menu_data_masking'], true)) {
                    return false;
                }
                return true;
            }));

            foreach ($groups as &$grp) {
                if (!empty($grp['permissions'])) {
                    $grp['permissions'] = array_values(array_filter($grp['permissions'], function ($p) use ($holdingOnlyPermissions) {
                        return !in_array($p['name'] ?? '', $holdingOnlyPermissions, true);
                    }));
                }
            }
            unset($grp);
        }
        return $groups;
    }

    /**
     * Ensure all permissions from registry exist in DB efficiently in bulk.
     */
    public function syncPermissionsToDb(): void
    {
        $allNames = \App\Support\PermissionRegistry::allNames();
        $existing = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        $missing = array_diff($allNames, $existing);
        if (!empty($missing)) {
            $records = array_map(fn($name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ], array_values($missing));
            Permission::insert($records);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);
        }
    }

    /**
     * Tandai role sebagai "sudah dikonfigurasi admin" agar tidak pernah ditimpa ulang
     * oleh permissions:sync / seeder saat deploy atau update menu.
     */
    private function markRoleCustomized(Role $role): void
    {
        try {
            $table = config('permission.table_names.roles', 'roles');
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'permissions_customized_at')) {
                \Illuminate\Support\Facades\DB::table($table)
                    ->where('id', $role->id)
                    ->update(['permissions_customized_at' => now()]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal menandai role customized: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        // Auto-sync permissions to DB on page load
        $this->syncPermissionsToDb();

        $cId = Role::resolveActiveCompanyId();
        $activeComp = \App\Models\Company::find($cId);
        $isMitra = $cId > 1 || ($activeComp && !$activeComp->is_holding);

        // Jika perusahaan aktif adalah Mitra (bukan Holding 1), pastikan seluruh role standar sudah ada
        if ($cId > 1) {
            $holdingRoles = Role::where(function ($q) {
                $q->where('company_id', 1)->orWhereNull('company_id');
            })->with('permissions')->get();

            $existingRoleNames = Role::where('company_id', $cId)->pluck('name')->toArray();

            foreach ($holdingRoles as $hr) {
                if (!in_array($hr->name, $existingRoleNames, true)) {
                    $newRole = Role::create([
                        'company_id' => $cId,
                        'name' => $hr->name,
                        'guard_name' => $hr->guard_name ?: 'web',
                    ]);
                    if ($hr->permissions->isNotEmpty()) {
                        $perms = $hr->permissions->pluck('name')->toArray();
                        if ($isMitra) {
                            $perms = array_values(array_diff($perms, self::$holdingOnlyPermissions));
                        }
                        $newRole->syncPermissions($perms);
                    }
                }
            }

            // Sanitasi: Pastikan role mitra di database bersih dari izin khusus holding
            if ($isMitra) {
                $mitraRoleIds = Role::where('company_id', $cId)->pluck('id');
                if ($mitraRoleIds->isNotEmpty()) {
                    \Illuminate\Support\Facades\DB::table('role_has_permissions')
                        ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                        ->whereIn('role_has_permissions.role_id', $mitraRoleIds)
                        ->whereIn('permissions.name', self::$holdingOnlyPermissions)
                        ->delete();
                }
            }
        }

        $roles = Role::forCurrentCompany()
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(function ($role) use ($cId, $isMitra) {
                $perms = $role->permissions->pluck('name')->toArray();
                if ($isMitra) {
                    $perms = array_values(array_diff($perms, self::$holdingOnlyPermissions));
                }
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $perms,
                    'users_count' => $role->users()->where(function ($uq) use ($cId) {
                        if ($cId === 1) {
                            $uq->where('users.company_id', 1)->orWhereNull('users.company_id');
                        } else {
                            $uq->where('users.company_id', $cId);
                        }
                    })->count(),
                ];
            });

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'permissionGroups' => $this->getPermissionRegistry($isMitra),
            'rolePresets' => \App\Support\PermissionRegistry::getDefaultRolePresets(),
            'positions' => \App\Models\Position::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->syncPermissionsToDb();
        $request->merge(['name' => strtolower(trim($request->name))]);
        $cId = Role::resolveActiveCompanyId();
        $activeComp = \App\Models\Company::find($cId);
        $isMitra = $cId > 1 || ($activeComp && !$activeComp->is_holding);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where(function ($q) use ($cId) {
                    if ($cId === 1) {
                        $q->where(fn($sub) => $sub->where('company_id', 1)->orWhereNull('company_id'));
                    } else {
                        $q->where('company_id', $cId);
                    }
                }),
            ],
            'permissions' => 'array',
        ]);

        $role = Role::create([
            'company_id' => $cId,
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions') && is_array($request->permissions)) {
            $perms = $request->permissions;
            if ($isMitra) {
                $perms = array_values(array_diff($perms, self::$holdingOnlyPermissions));
            }
            $role->syncPermissions($perms);
        }
        $this->markRoleCustomized($role);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, Role $role)
    {
        $this->syncPermissionsToDb();
        $request->merge(['name' => strtolower(trim($request->name))]);
        $cId = Role::resolveActiveCompanyId();
        $activeComp = \App\Models\Company::find($cId);
        $isMitra = $cId > 1 || ($activeComp && !$activeComp->is_holding);

        // Validasi kepemilikan role
        if ($cId === 1) {
            if ($role->company_id && $role->company_id !== 1) {
                abort(403, 'Role ini bukan milik Pusat.');
            }
        } else {
            if ($role->company_id !== $cId) {
                abort(403, 'Role ini bukan milik perusahaan Anda.');
            }
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where(function ($q) use ($cId) {
                    if ($cId === 1) {
                        $q->where(fn($sub) => $sub->where('company_id', 1)->orWhereNull('company_id'));
                    } else {
                        $q->where('company_id', $cId);
                    }
                })->ignore($role->id),
            ],
            'permissions' => 'array',
        ]);

        if ($role->name === 'admin') {
            if ($request->name !== 'admin') {
                return redirect()->back()->with('error', 'Nama role Admin tidak dapat diubah.');
            }
            $allPerms = Permission::where('guard_name', 'web')->pluck('name')->toArray();
            if ($isMitra) {
                $allPerms = array_values(array_diff($allPerms, self::$holdingOnlyPermissions));
            }
            $role->syncPermissions($allPerms);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);
            return redirect()->back()->with('success', "Role Admin terkunci permanen dengan akses penuh.");
        }

        $role->update(['name' => $request->name]);

        // PENTING: hanya sinkronkan izin jika payload benar-benar mengirim daftar izin.
        if ($request->exists('permissions') && is_array($request->input('permissions'))) {
            $perms = $request->input('permissions');
            if ($isMitra) {
                $perms = array_values(array_diff($perms, self::$holdingOnlyPermissions));
            }
            $role->syncPermissions($perms);
            $this->markRoleCustomized($role);
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil diperbarui.");
    }

    public function clone(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $targetName = strtolower(trim($request->name));
        $cId = Role::resolveActiveCompanyId();
        $activeComp = \App\Models\Company::find($cId);
        $isMitra = $cId > 1 || ($activeComp && !$activeComp->is_holding);

        if ($targetName === strtolower($role->name)) {
            return redirect()->back()->with('error', "Nama role tujuan tidak boleh sama persis dengan role sumber.");
        }

        // Proteksi: Role Super Admin tidak boleh ditimpa
        if (in_array($targetName, ['admin', 'super admin', 'superadmin'], true)) {
            return redirect()->back()->with('error', "Role Super Admin tidak dapat ditimpa melalui duplikasi.");
        }

        // Jika sumber adalah Admin/Super Admin, pastikan menduplikasi seluruh 704 izin
        if (in_array(strtolower($role->name), ['admin', 'super admin', 'superadmin'], true)) {
            $sourcePermissions = \App\Support\PermissionRegistry::allNames();
        } else {
            $sourcePermissions = $role->permissions->pluck('name')->toArray();
        }

        if ($isMitra) {
            $sourcePermissions = array_values(array_diff($sourcePermissions, self::$holdingOnlyPermissions));
        }

        $existingRole = Role::where(function ($q) use ($cId) {
            if ($cId === 1) {
                $q->where('company_id', 1)->orWhereNull('company_id');
            } else {
                $q->where('company_id', $cId);
            }
        })->where('name', $targetName)->first();

        if ($existingRole) {
            $existingRole->syncPermissions($sourcePermissions);
            $this->markRoleCustomized($existingRole);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);

            return redirect()->back()->with('success', "Seluruh izin (" . count($sourcePermissions) . " izin) dari role '{$role->name}' berhasil disalin ke role '{$existingRole->name}'.");
        }

        $newRole = Role::create([
            'company_id' => $cId,
            'name' => $targetName,
            'guard_name' => 'web',
        ]);
        $newRole->syncPermissions($sourcePermissions);
        $this->markRoleCustomized($newRole);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);

        return redirect()->back()->with('success', "Role baru '{$newRole->name}' berhasil dibuat dan seluruh izin dari '{$role->name}' berhasil diduplikasi.");
    }

    public function applyPreset(Request $request, Role $role)
    {
        $cId = Role::resolveActiveCompanyId();
        if ($cId === 1) {
            if ($role->company_id && $role->company_id !== 1) {
                abort(403, 'Role ini bukan milik Pusat.');
            }
        } else {
            if ($role->company_id !== $cId) {
                abort(403, 'Role ini bukan milik perusahaan Anda.');
            }
        }

        $presetKey = strtolower(trim($request->input('preset', $role->name)));
        $presets = \App\Support\PermissionRegistry::getDefaultRolePresets();

        // Fallback matching
        if (!isset($presets[$presetKey])) {
            foreach ($presets as $k => $v) {
                if (str_contains($presetKey, $k) || str_contains($k, $presetKey)) {
                    $presetKey = $k;
                    break;
                }
            }
        }

        if (!isset($presets[$presetKey])) {
            return redirect()->back()->with('error', "Template preset untuk '{$presetKey}' tidak ditemukan.");
        }

        $activeComp = \App\Models\Company::find($cId);
        $isMitra = $cId > 1 || ($activeComp && !$activeComp->is_holding);

        $permissions = $presets[$presetKey];
        if (in_array(strtolower($role->name), ['admin', 'super admin', 'superadmin'], true)) {
            $allPerms = Permission::where('guard_name', 'web')->pluck('name')->toArray();
            if ($isMitra) {
                $allPerms = array_values(array_diff($allPerms, self::$holdingOnlyPermissions));
            }
            $role->syncPermissions($allPerms);
        } else {
            if ($isMitra) {
                $permissions = array_values(array_diff($permissions, self::$holdingOnlyPermissions));
            }
            $role->syncPermissions($permissions);
            $this->markRoleCustomized($role);

            // Samakan teknisi-2 dengan teknisi secara otomatis jika di company yang sama
            if ($role->name === 'teknisi') {
                $t2 = Role::where(function ($q) use ($cId) {
                    if ($cId === 1) {
                        $q->where('company_id', 1)->orWhereNull('company_id');
                    } else {
                        $q->where('company_id', $cId);
                    }
                })->where('name', 'teknisi-2')->first();
                if ($t2) {
                    $t2->syncPermissions($permissions);
                    $this->markRoleCustomized($t2);
                }
            } elseif ($role->name === 'teknisi-2') {
                $t = Role::where(function ($q) use ($cId) {
                    if ($cId === 1) {
                        $q->where('company_id', 1)->orWhereNull('company_id');
                    } else {
                        $q->where('company_id', $cId);
                    }
                })->where('name', 'teknisi')->first();
                if ($t) {
                    $t->syncPermissions($permissions);
                    $this->markRoleCustomized($t);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);

        return redirect()->back()->with('success', "Template preset '{$presetKey}' berhasil diterapkan ke role '{$role->name}' (" . count($permissions) . " izin).");
    }

    public function destroy(Role $role)
    {
        $cId = Role::resolveActiveCompanyId();
        if ($cId === 1) {
            if ($role->company_id && $role->company_id !== 1) {
                abort(403, 'Role ini bukan milik Pusat.');
            }
        } else {
            if ($role->company_id !== $cId) {
                abort(403, 'Role ini bukan milik perusahaan Anda.');
            }
        }

        if (in_array(strtolower($role->name), ['admin', 'super admin', 'superadmin'], true)) {
            return redirect()->back()->with('error', 'Role Admin / Super Admin tidak dapat dihapus demi keamanan sistem.');
        }

        $usersInThisCompany = $role->users()->where(function ($uq) use ($cId) {
            if ($cId === 1) {
                $uq->where('users.company_id', 1)->orWhereNull('users.company_id');
            } else {
                $uq->where('users.company_id', $cId);
            }
        })->count();

        if ($usersInThisCompany > 0) {
            return redirect()->back()->with('error', "Role '{$role->name}' masih digunakan oleh {$usersInThisCompany} user di perusahaan ini. Pindahkan user terlebih dahulu.");
        }

        $role->delete();

        return redirect()->back()->with('success', "Role berhasil dihapus.");
    }
}
