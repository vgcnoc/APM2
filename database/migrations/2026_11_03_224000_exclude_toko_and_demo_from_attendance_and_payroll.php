<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bersihkan data gaji, insentif, dan potongan pada User Toko & Demo
        if (Schema::hasTable('users')) {
            $exemptUserIds = User::with('roles')
                ->get()
                ->filter(fn (User $u) => $u->isExemptFromAttendanceAndPayroll())
                ->pluck('id')
                ->values()
                ->all();

            if (!empty($exemptUserIds)) {
                $updateUserCols = ['base_salary' => 0];
                if (Schema::hasColumn('users', 'incentive_rate')) {
                    $updateUserCols['incentive_rate'] = 0;
                }
                if (Schema::hasColumn('users', 'bpjs_kesehatan')) {
                    $updateUserCols['bpjs_kesehatan'] = false;
                }
                if (Schema::hasColumn('users', 'bpjs_ketenagakerjaan')) {
                    $updateUserCols['bpjs_ketenagakerjaan'] = false;
                }

                DB::table('users')
                    ->whereIn('id', $exemptUserIds)
                    ->update($updateUserCols);

                if (Schema::hasTable('deductions')) {
                    DB::table('deductions')
                        ->whereIn('user_id', $exemptUserIds)
                        ->delete();
                }

                if (Schema::hasTable('incentives')) {
                    DB::table('incentives')
                        ->whereIn('user_id', $exemptUserIds)
                        ->delete();
                }

                if (Schema::hasTable('payrolls')) {
                    DB::table('payrolls')
                        ->whereIn('user_id', $exemptUserIds)
                        ->where('status', '!=', 'paid')
                        ->delete();
                }
            }
        }

        // 2. Bersihkan data absen, jadwal shift, izin/alpha, dan gaji pokok pada Employee Toko & Demo
        if (Schema::hasTable('employees')) {
            $exemptEmpIds = Employee::query()
                ->get()
                ->filter(fn (Employee $e) => $e->isExemptFromAttendanceAndPayroll())
                ->pluck('id')
                ->values()
                ->all();

            if (!empty($exemptEmpIds)) {
                $updateEmpCols = [];
                if (Schema::hasColumn('employees', 'base_salary')) {
                    $updateEmpCols['base_salary'] = 0;
                }
                if (Schema::hasColumn('employees', 'shift_id')) {
                    $updateEmpCols['shift_id'] = null;
                }
                if (!empty($updateEmpCols)) {
                    DB::table('employees')
                        ->whereIn('id', $exemptEmpIds)
                        ->update($updateEmpCols);
                }

                foreach (['attendances', 'special_attendances', 'work_schedules', 'attendance_leaves', 'field_duties', 'special_team_members'] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)
                            ->whereIn('employee_id', $exemptEmpIds)
                            ->delete();
                    }
                }
            }
        }

        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // Ignore cache flush error in CLI if driver is unavailable
        }
    }

    public function down(): void
    {
        // One-way cleanup migration for Toko and Demo accounts
    }
};

