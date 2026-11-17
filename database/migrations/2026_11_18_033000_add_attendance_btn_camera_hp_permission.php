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
     * Daftarkan permission 'attendance_btn_camera_hp' ke sistem hak akses.
     * Secara default dibiarkan tidak dicentang untuk semua role (hanya admin/superadmin yang bypass).
     */
    public function up(): void
    {
        // 1. Buat permission jika belum ada
        $perm = Permission::firstOrCreate([
            'name' => 'attendance_btn_camera_hp',
            'guard_name' => 'web',
        ]);

        // 2. Berikan hanya ke role admin
        $adminRoles = ['admin', 'Admin'];
        foreach ($adminRoles as $adminRoleName) {
            $adminRole = Role::where('name', $adminRoleName)->where('guard_name', 'web')->first();
            if ($adminRole) {
                $adminRole->givePermissionTo($perm);
            }
        }

        // 3. Pastikan seluruh role non-admin tidak memiliki permission ini secara default
        $nonAdminRoles = Role::whereNotIn('name', $adminRoles)
            ->where('guard_name', 'web')
            ->get();

        foreach ($nonAdminRoles as $role) {
            if ($role->hasPermissionTo('attendance_btn_camera_hp')) {
                $role->revokePermissionTo('attendance_btn_camera_hp');
            }
        }

        // 4. Reset permission cache Spatie & increment inertia rbac version
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::forget('spatie.permission.cache');
        Cache::put('inertia_rbac_ver', time(), 86400);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = Permission::where('name', 'attendance_btn_camera_hp')->first();
        if ($perm) {
            $perm->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::forget('spatie.permission.cache');
        Cache::put('inertia_rbac_ver', time(), 86400);
    }
};

