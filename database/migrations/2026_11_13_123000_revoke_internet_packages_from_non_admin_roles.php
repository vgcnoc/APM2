<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $packagePermNames = [
            'menu_internet_packages',
            'internet_packages_card_stats',
            'internet_packages_filter',
            'internet_packages_view',
            'internet_packages_create',
            'internet_packages_edit',
            'internet_packages_delete',
        ];

        // 1. Dapatkan ID seluruh permissions terkait paket internet
        $permIds = DB::table('permissions')
            ->whereIn('name', $packagePermNames)
            ->pluck('id')
            ->toArray();

        if (!empty($permIds)) {
            // 2. Dapatkan ID role non-admin (kecualikan 'admin', 'super admin', 'superadmin')
            $nonAdminRoleIds = DB::table('roles')
                ->whereNotIn(DB::raw("LOWER(TRIM(name))"), ['admin', 'super admin', 'superadmin'])
                ->pluck('id')
                ->toArray();

            if (!empty($nonAdminRoleIds)) {
                // Hapus asosiasi dari role_has_permissions
                DB::table('role_has_permissions')
                    ->whereIn('permission_id', $permIds)
                    ->whereIn('role_id', $nonAdminRoleIds)
                    ->delete();
            }

            // Hapus juga direct permissions pada user non-admin jika ada di model_has_permissions
            $nonAdminUserIds = DB::table('users')
                ->whereNotIn(DB::raw("LOWER(TRIM(COALESCE(role, '')))"), ['admin', 'super admin', 'superadmin'])
                ->pluck('id')
                ->toArray();

            if (!empty($nonAdminUserIds)) {
                DB::table('model_has_permissions')
                    ->whereIn('permission_id', $permIds)
                    ->whereIn('model_id', $nonAdminUserIds)
                    ->delete();
            }
        }

        // 3. Reset cache Spatie & Inertia RBAC version agar session langsung segar
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            Cache::put('inertia_rbac_ver', time(), 86400);
        } catch (\Throwable $e) {
            // Ignore if in console without cache initialized
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
