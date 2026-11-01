<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reset permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Daftarkan permission attendance_bypass_early_restriction
        $perm = Permission::firstOrCreate([
            'name' => 'attendance_bypass_early_restriction',
            'guard_name' => 'web',
        ]);

        // 3. Berikan permission ke role super admin, admin, dan hrd
        $roles = Role::whereIn('name', ['super admin', 'admin', 'hrd'])->where('guard_name', 'web')->get();
        foreach ($roles as $role) {
            if (!$role->hasPermissionTo('attendance_bypass_early_restriction')) {
                $role->givePermissionTo('attendance_bypass_early_restriction');
            }
        }

        // 4. Default Setting jika belum ada
        if (Setting::get('attendance_clock_in_early_limit_enabled') === null) {
            Setting::set('attendance_clock_in_early_limit_enabled', '1');
        }
        if (Setting::get('attendance_clock_in_early_limit_minutes') === null) {
            Setting::set('attendance_clock_in_early_limit_minutes', '60');
        }

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

