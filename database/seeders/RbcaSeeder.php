<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RbcaSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'booking_create',
            'jadwal_survey',
            'laporan_survey',
            'ready_instalasi',
            'jadwal_pasang',
            'laporan_instalasi',
            'audit',
            'aktivasi',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 1. Karyawan
        $roleKaryawan = Role::firstOrCreate(['name' => 'Karyawan']);
        $roleKaryawan->syncPermissions(['booking_create', 'laporan_survey']);

        // 2. Teknisi
        $roleTeknisi = Role::firstOrCreate(['name' => 'Teknisi']);
        $roleTeknisi->syncPermissions(['booking_create', 'laporan_survey', 'laporan_instalasi']);

        // 3. User Survey/Dispatcher
        $roleDispatcher = Role::firstOrCreate(['name' => 'Dispatcher']);
        $roleDispatcher->syncPermissions(['booking_create', 'laporan_survey', 'jadwal_survey', 'ready_instalasi', 'jadwal_pasang']);

        // 4. Auditor
        $roleAuditor = Role::firstOrCreate(['name' => 'Auditor']);
        $roleAuditor->syncPermissions(['booking_create', 'laporan_survey', 'audit']);

        // 5. Admin/Manager
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleAdmin->syncPermissions(Permission::all());

        // 6. Aktivator
        $roleAktivator = Role::firstOrCreate(['name' => 'Aktivator']);
        $roleAktivator->syncPermissions(['booking_create', 'laporan_survey', 'aktivasi']);
        
        // Sync to existing users
        foreach (\App\Models\User::all() as $user) {
            $user->syncRoles([]);
            if ($user->role) {
                $roleName = ucfirst($user->role);
                // Assign role if it exists, otherwise assign Karyawan
                if (Role::where('name', $roleName)->exists()) {
                    $user->assignRole($roleName);
                } else {
                    $user->assignRole('Karyawan');
                }
            } else {
                $user->assignRole('Karyawan');
            }
        }
    }
}
