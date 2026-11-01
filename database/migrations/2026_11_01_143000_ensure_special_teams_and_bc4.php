<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SpecialTeam;
use App\Models\SpecialTeamMember;
use App\Models\Employee;
use App\Models\Area;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pastikan TIM BC-4 terdaftar di tabel special_teams
        $bc4Team = SpecialTeam::where(function ($q) {
            $q->where('name', 'like', '%bc-4%')
              ->orWhere('name', 'like', '%bc 4%')
              ->orWhere('name', 'like', '%bc4%')
              ->orWhere('code', 'BC-04')
              ->orWhere('code', 'BC-4');
        })->first();

        if (!$bc4Team) {
            $bc4Team = SpecialTeam::create([
                'name' => 'TIM BC-4',
                'code' => 'BC-04',
                'start_time' => '08:30:00',
                'end_time' => '18:00:00',
                'tolerance_minutes' => 15,
                'without_radius' => true,
                'late_deduction_amount' => 10000,
                'no_out_deduction_amount' => 10000,
                'alpha_deduction_amount' => 35000,
                'description' => 'Grup absensi tim operasional BC-4 tanpa batasan radius kantor.',
                'is_active' => true,
            ]);
        }

        // 2. Pastikan GRUP TIM KHUSUS dan TIM SERBU terdaftar
        $tk = SpecialTeam::where('name', 'like', '%khusus%')->orWhere('code', 'TK-01')->first();
        if (!$tk) {
            SpecialTeam::create([
                'name' => 'GRUP TIM KHUSUS',
                'code' => 'TK-01',
                'start_time' => '08:30:00',
                'end_time' => '18:00:00',
                'tolerance_minutes' => 15,
                'without_radius' => true,
                'late_deduction_amount' => 10000,
                'no_out_deduction_amount' => 10000,
                'alpha_deduction_amount' => 35000,
                'description' => 'Grup absensi tim khusus operasional dan lapangan tanpa batasan radius kantor.',
                'is_active' => true,
            ]);
        }

        $ts = SpecialTeam::where('name', 'like', '%serbu%')->orWhere('code', 'TS-01')->first();
        if (!$ts) {
            SpecialTeam::create([
                'name' => 'TIM SERBU',
                'code' => 'TS-01',
                'start_time' => '08:30:00',
                'end_time' => '18:00:00',
                'tolerance_minutes' => 15,
                'without_radius' => true,
                'late_deduction_amount' => 10000,
                'no_out_deduction_amount' => 10000,
                'alpha_deduction_amount' => 35000,
                'description' => 'Grup absensi tim serbu maintenance dan insidental tanpa batasan radius kantor.',
                'is_active' => true,
            ]);
        }

        // 3. Tautkan karyawan di area BC 4 atau branch BC 4 ke TIM BC-4 jika belum memiliki tim khusus
        if ($bc4Team) {
            $bc4AreaIds = Area::where('name', 'like', '%BC%4%')->pluck('id')->toArray();
            $bc4Employees = Employee::where(function ($q) use ($bc4AreaIds) {
                if (!empty($bc4AreaIds)) {
                    $q->whereIn('area_id', $bc4AreaIds);
                }
                $q->orWhere('branch', 'like', '%BC%4%')
                  ->orWhere('branch', 'like', '%BC-4%');
            })->where(function ($q) {
                $q->where('status', '!=', 'resigned')->orWhereNull('status');
            })->get();

            foreach ($bc4Employees as $emp) {
                $hasTeam = SpecialTeamMember::where('employee_id', $emp->id)->exists();
                if (!$hasTeam) {
                    SpecialTeamMember::create([
                        'special_team_id' => $bc4Team->id,
                        'employee_id' => $emp->id,
                        'role' => 'Teknisi BC-4',
                        'is_leader' => false,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

