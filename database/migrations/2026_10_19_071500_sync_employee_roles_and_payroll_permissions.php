<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Employee;
use App\Http\Controllers\EmployeeController;
use App\Support\PermissionRegistry;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reset cache izin
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Buat seluruh master permissions
        $allNames = PermissionRegistry::allNames();
        foreach ($allNames as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // 3. Pastikan role admin punya semua permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        // 4. Sinkronisasi preset role
        $presets = PermissionRegistry::getDefaultRolePresets();
        foreach ($presets as $roleName => $perms) {
            if ($roleName === 'admin') continue;

            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $validPerms = Permission::whereIn('name', $perms)->pluck('name')->toArray();
            $role->givePermissionTo($validPerms);
        }

        // 5. Normalisasi role user berdasarkan data jabatan (Kolektor -> sales, Infrastruktur -> teknisi, dsb)
        $users = User::all();
        foreach ($users as $user) {
            $emp = Employee::where('email', $user->email)
                ->orWhere(function ($q) use ($user) {
                    if (!empty($user->phone)) {
                        $q->where('phone', $user->phone);
                    }
                })
                ->orWhere('name', $user->name)
                ->first();

            $pos = $emp ? $emp->position : ($user->job_position ?: $user->role);
            if (!empty($pos)) {
                $mappedRole = EmployeeController::mapPositionToRole($pos);
                if (!empty($mappedRole) && $mappedRole !== 'karyawan') {
                    $user->update(['role' => $mappedRole]);
                    $targetRole = Role::firstOrCreate(['name' => $mappedRole, 'guard_name' => 'web']);
                    if (!$user->hasRole($mappedRole)) {
                        $user->assignRole($mappedRole);
                    }
                }
            }
        }

        // Reset cache kembali
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
