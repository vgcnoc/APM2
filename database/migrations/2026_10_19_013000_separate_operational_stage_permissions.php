<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure new permissions exist
        $newPermissions = [
            'menu_cbp_jadwal',
            'menu_cbp_laporan',
            'menu_pgk_jadwal',
            'menu_pgk_laporan',
            'menu_pgj_jadwal',
            'menu_pgj_laporan',
            'menu_pgs_jadwal',
            'menu_pgs_laporan',
        ];

        foreach ($newPermissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        // 2. Adjust role permissions
        // Roles for ground technicians: only penanganan/laporan, remove stage 1 & 2
        $techRoles = Role::whereIn('name', ['teknisi', 'maintenance'])->get();
        foreach ($techRoles as $role) {
            // Remove stage 1 (intake/admin)
            $role->revokePermissionTo(array_intersect(
                $role->permissions->pluck('name')->toArray(),
                ['menu_cbp', 'menu_pgk', 'menu_pgj', 'menu_pgs']
            ));
            // Grant stage 3 (laporan penanganan)
            $role->givePermissionTo([
                'menu_cbp_laporan',
                'menu_pgk_laporan',
                'menu_pgj_laporan',
                'menu_pgs_laporan',
            ]);
        }

        // Role kepala teknisi: all stages
        $leadTech = Role::where('name', 'kepala teknisi')->first();
        if ($leadTech) {
            $leadTech->givePermissionTo([
                'menu_cbp', 'menu_cbp_jadwal', 'menu_cbp_laporan',
                'menu_pgk', 'menu_pgk_jadwal', 'menu_pgk_laporan',
                'menu_pgj', 'menu_pgj_jadwal', 'menu_pgj_laporan',
                'menu_pgs', 'menu_pgs_jadwal', 'menu_pgs_laporan',
            ]);
        }

        // Role dispatcher: stage 1 & 2
        $dispatcher = Role::where('name', 'dispatcher')->first();
        if ($dispatcher) {
            $dispatcher->givePermissionTo([
                'menu_cbp', 'menu_cbp_jadwal',
                'menu_pgk', 'menu_pgk_jadwal',
                'menu_pgj', 'menu_pgj_jadwal',
                'menu_pgs', 'menu_pgs_jadwal',
            ]);
        }

        // Role admin: all permissions
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->syncPermissions(Permission::where('guard_name', 'web')->get());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'menu_cbp_jadwal',
            'menu_cbp_laporan',
            'menu_pgk_jadwal',
            'menu_pgk_laporan',
            'menu_pgj_jadwal',
            'menu_pgj_laporan',
            'menu_pgs_jadwal',
            'menu_pgs_laporan',
        ];

        Permission::whereIn('name', $permissions)->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
