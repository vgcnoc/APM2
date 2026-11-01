<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat tabel attendance_leaves
        if (!Schema::hasTable('attendance_leaves')) {
            Schema::create('attendance_leaves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('date');
                $table->string('type', 30); // sick_with_cert, sick_no_cert, permit, alpha, leave
                $table->text('reason')->nullable();
                $table->string('attachment')->nullable();
                $table->decimal('penalty_amount', 12, 2)->default(0);
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('work_schedule_id')->nullable()->constrained('work_schedules')->nullOnDelete();
                $table->timestamps();

                $table->unique(['employee_id', 'date']);
                $table->index('date');
                $table->index('type');
            });
        }

        // 2. Daftarkan Hak Akses / Permission baru
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'menu_attendance_leaves',
            'attendance_leaves_manage',
            'attendance_leaves_delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        // 3. Berikan izin khusus ke Admin, Super Admin, HRD, dan Finance (TIDAK DIBERIKAN KE MANAGER)
        $allowedRoles = Role::whereIn('name', ['admin', 'superadmin', 'hrd', 'finance'])->where('guard_name', 'web')->get();
        foreach ($allowedRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        // 4. Pastikan role 'manager' secara tegas TIDAK memiliki izin ini
        $managerRole = Role::where('name', 'manager')->where('guard_name', 'web')->first();
        if ($managerRole) {
            foreach ($permissions as $permName) {
                if ($managerRole->hasPermissionTo($permName)) {
                    $managerRole->revokePermissionTo($permName);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_leaves');

        $permissions = [
            'menu_attendance_leaves',
            'attendance_leaves_manage',
            'attendance_leaves_delete',
        ];

        foreach ($permissions as $permName) {
            Permission::where('name', $permName)->delete();
        }
    }
};

