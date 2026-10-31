<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\SpecialAttendance;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceCalculatorService
{
    /**
     * Dapatkan detail periode bulan cerdas: dari tanggal 01 awal bulan s/d akhir bulan (28/29/30/31).
     * Menghitung total hari kalender, total hari kerja, dan hari kerja yang telah berjalan.
     */
    public static function getMonthPeriodDetails(?string $month = null): array
    {
        $today = Carbon::today('Asia/Jakarta');
        
        if (empty($month)) {
            $month = $today->format('Y-m');
        }

        $startOfMonth = Carbon::parse($month . '-01', 'Asia/Jakarta')->startOfMonth()->startOfDay();
        $endOfMonth = (clone $startOfMonth)->endOfMonth()->endOfDay();

        $isCurrentMonth = ($month === $today->format('Y-m'));
        $isPastMonth = $startOfMonth->isBefore($today->copy()->startOfMonth());
        $isFutureMonth = $startOfMonth->isAfter($today->copy()->endOfMonth());

        // Batas hari berjalan (jika bulan berjalan, batasnya adalah hari ini; jika masa lalu, batasnya akhir bulan; jika masa depan, batasnya 0)
        $effectiveCutoffDate = $isCurrentMonth 
            ? $today->copy() 
            : ($isPastMonth ? $endOfMonth->copy() : $startOfMonth->copy()->subDay());

        // Hitung hari kerja (Senin - Sabtu, Minggu off)
        $totalCalendarDays = $startOfMonth->daysInMonth;
        $totalWorkDays = 0;
        $elapsedWorkDays = 0;

        $cursor = $startOfMonth->copy();
        while ($cursor->lte($endOfMonth)) {
            // Standar operasional: hari kerja selain hari Minggu
            if (!$cursor->isSunday()) {
                $totalWorkDays++;
                if ($cursor->lte($effectiveCutoffDate)) {
                    $elapsedWorkDays++;
                }
            }
            $cursor->addDay();
        }

        return [
            'month' => $month,
            'year' => (int) $startOfMonth->format('Y'),
            'month_number' => (int) $startOfMonth->format('m'),
            'month_label' => $startOfMonth->translatedFormat('F Y'),
            'start_date' => $startOfMonth->format('Y-m-d'),
            'end_date' => $endOfMonth->format('Y-m-d'),
            'start_datetime' => $startOfMonth,
            'end_datetime' => $endOfMonth,
            'total_calendar_days' => $totalCalendarDays,
            'total_work_days' => $totalWorkDays,
            'elapsed_work_days' => $elapsedWorkDays,
            'is_current_month' => $isCurrentMonth,
            'is_past_month' => $isPastMonth,
            'is_future_month' => $isFutureMonth,
            'period_label' => "01 {$startOfMonth->translatedFormat('M Y')} s/d {$endOfMonth->translatedFormat('d M Y')}",
        ];
    }

    /**
     * Ambil pengaturan penalti dan aturan presensi
     */
    public static function getAttendanceSettings(): array
    {
        $latePenaltyMode = Setting::get('attendance_late_mode', Setting::get('attendance_late_penalty_mode', 'Per Kejadian'));
        $latePenaltyModeSlug = str_contains(strtolower($latePenaltyMode), 'menit') ? 'per_menit' : 'per_kejadian';

        return [
            'late_enabled' => Setting::get('attendance_late_enabled', Setting::get('attendance_late_penalty_enabled', '1')) === '1',
            'late_mode' => $latePenaltyModeSlug,
            'late_fixed_amount' => (float) Setting::get('attendance_late_amount', 10000),
            'late_per_minute_amount' => (float) Setting::get('attendance_late_per_minute_amount', Setting::get('attendance_late_penalty_per_minute', 1000)),
            'late_tolerance_minutes' => (int) Setting::get('attendance_late_tolerance', 15),

            'missing_out_enabled' => Setting::get('attendance_missing_out_enabled', Setting::get('attendance_missing_out_penalty_enabled', '1')) === '1',
            'missing_out_amount' => (float) Setting::get('attendance_missing_out_amount', 10000),

            'early_leave_enabled' => Setting::get('attendance_early_leave_enabled', Setting::get('attendance_early_leave_penalty_enabled', '1')) === '1',
            'early_leave_amount' => (float) Setting::get('attendance_early_leave_amount', 10000),

            'alpha_enabled' => Setting::get('attendance_alpha_enabled', Setting::get('attendance_alpha_penalty_enabled', '1')) === '1',
            'alpha_amount' => (float) Setting::get('attendance_alpha_amount', 35000),

            'checkout_deadline_time' => Setting::get('attendance_checkout_deadline_time', '18:30'),
            'cron_enabled' => Setting::get('attendance_cron_enabled', '1') === '1',
        ];
    }

    /**
     * Hitung rekapitulasi presensi bulanan per karyawan secara cerdas dan otomatis
     */
    public static function calculateMonthlyRecap(
        Collection $employees,
        Collection $attendances,
        array $periodDetails,
        ?array $settings = null,
        ?Collection $workSchedules = null
    ): Collection {
        $settings = $settings ?? self::getAttendanceSettings();
        $today = Carbon::today('Asia/Jakarta');
        $todayDate = $today->format('Y-m-d');
        $elapsedWorkDays = $periodDetails['elapsed_work_days'];
        $totalWorkDays = $periodDetails['total_work_days'];
        $cutoffTime = $settings['checkout_deadline_time'];

        return $employees->map(function ($emp) use (
            $attendances,
            $workSchedules,
            $settings,
            $today,
            $todayDate,
            $cutoffTime,
            $elapsedWorkDays,
            $totalWorkDays
        ) {
            $empAtts = $attendances->where('employee_id', $emp->id);

            // 1. Kehadiran
            $totalPresent = $empAtts->whereNotNull('clock_in')->count();

            // 2. Keterlambatan
            $lateAtts = $empAtts->filter(function ($a) {
                return $a->status === 'late' || (int) ($a->late_minutes ?? 0) > 0;
            });
            $totalLate = $lateAtts->count();
            $totalLateMinutes = (int) $empAtts->sum('late_minutes');

            // 3. Dinas Luar
            $totalFieldDuty = $empAtts->where('attendance_type', 'dinas_luar')->count();

            // 4. Lupa Clock-Out (Missing Out)
            $missingOutAtts = $empAtts->filter(function ($a) use ($todayDate, $cutoffTime) {
                $attDateStr = is_string($a->date) ? substr($a->date, 0, 10) : $a->date->format('Y-m-d');
                if (empty($a->clock_in) || !empty($a->clock_out)) {
                    return false;
                }
                if ($attDateStr < $todayDate) {
                    return true;
                }
                if ($attDateStr === $todayDate && Carbon::now('Asia/Jakarta')->greaterThan(Carbon::parse($attDateStr . ' ' . $cutoffTime, 'Asia/Jakarta'))) {
                    return true;
                }
                return false;
            });
            $totalMissingOut = $missingOutAtts->count();

            // 5. Pulang Cepat
            $earlyAtts = $empAtts->filter(function ($a) {
                $earlyMins = (int) ($a->early_leave_minutes ?? 0);
                if ($earlyMins > 0) return true;
                return str_contains(strtolower($a->notes ?? ''), 'pulang lebih awal');
            });
            $totalEarlyLeave = $earlyAtts->count();

            // 6. Tepat Waktu
            $totalOnTime = max(0, $totalPresent - $totalLate - $totalFieldDuty);

            // 7. Hitung Hari Kerja Individu & Alpha Secara Cerdas
            // Jika ada WorkSchedule terjadwal, gunakan data jadwal kerja
            $empWorkDays = $totalWorkDays;
            $empElapsedWorkDays = $elapsedWorkDays;

            if ($workSchedules && $workSchedules->isNotEmpty()) {
                $empSchedules = $workSchedules->where('employee_id', $emp->id);
                if ($empSchedules->isNotEmpty()) {
                    $empWorkDays = $empSchedules->where('status', 'work')->count();
                    $empElapsedWorkDays = $empSchedules->where('status', 'work')
                        ->filter(function ($s) use ($todayDate) {
                            $sDate = is_string($s->date) ? substr($s->date, 0, 10) : $s->date->format('Y-m-d');
                            return $sDate <= $todayDate;
                        })->count();
                }
            }

            // Alpha = hari kerja yang telah berjalan dikurangi kehadiran & dinas luar
            $effectiveAttendedDays = min($empElapsedWorkDays, $totalPresent + $totalFieldDuty);
            $totalAlpha = max(0, $empElapsedWorkDays - $effectiveAttendedDays);

            // 8. Rasio Kehadiran & Skor Kedisiplinan
            $attendanceRate = $empElapsedWorkDays > 0 
                ? round(($effectiveAttendedDays / $empElapsedWorkDays) * 100, 1) 
                : 0;

            $disciplineRate = $totalPresent > 0 
                ? round(($totalOnTime / $totalPresent) * 100, 1) 
                : ($totalAlpha > 0 ? 0 : 100);

            // 9. Kalkulasi Penalti / Estimasi Potongan Cerdas
            $latePenalty = 0;
            if ($settings['late_enabled'] && $totalLate > 0) {
                $latePenalty = ($settings['late_mode'] === 'per_menit')
                    ? ($totalLateMinutes * $settings['late_per_minute_amount'])
                    : ($totalLate * $settings['late_fixed_amount']);
            }

            $missingOutPenalty = 0;
            if ($settings['missing_out_enabled'] && $totalMissingOut > 0) {
                $missingOutPenalty = $totalMissingOut * $settings['missing_out_amount'];
            }

            $earlyLeavePenalty = 0;
            if ($settings['early_leave_enabled'] && $totalEarlyLeave > 0) {
                $earlyLeavePenalty = $totalEarlyLeave * $settings['early_leave_amount'];
            }

            $alphaPenalty = 0;
            if ($settings['alpha_enabled'] && $totalAlpha > 0) {
                $alphaPenalty = $totalAlpha * $settings['alpha_amount'];
            }

            $totalEstimatedPenalty = $latePenalty + $missingOutPenalty + $earlyLeavePenalty + $alphaPenalty;

            // 10. Status Payroll Sync
            $syncedCount = $empAtts->where('is_synced_to_payroll', true)->count();
            $isAllSynced = $empAtts->isNotEmpty() 
                ? ($syncedCount >= $empAtts->count()) 
                : true;

            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'iak_number' => $emp->iak_number,
                'position' => $emp->position,
                'area_name' => $emp->area?->name ?? '-',
                'shift_name' => $emp->shift?->name ?? 'Reguler',
                'total_work_days' => $empWorkDays,
                'elapsed_work_days' => $empElapsedWorkDays,
                'total_present' => $totalPresent,
                'total_on_time' => $totalOnTime,
                'total_late' => $totalLate,
                'total_late_minutes' => $totalLateMinutes,
                'total_field_duty' => $totalFieldDuty,
                'total_missing_out' => $totalMissingOut,
                'total_early_leave' => $totalEarlyLeave,
                'total_alpha' => $totalAlpha,
                'attendance_rate' => $attendanceRate,
                'discipline_rate' => $disciplineRate,
                'penalties' => [
                    'late' => $latePenalty,
                    'missing_out' => $missingOutPenalty,
                    'early_leave' => $earlyLeavePenalty,
                    'alpha' => $alphaPenalty,
                    'total' => $totalEstimatedPenalty,
                ],
                'estimated_penalty' => $totalEstimatedPenalty,
                'is_all_synced' => $isAllSynced,
            ];
        })->values();
    }

    /**
     * Eksekusi sinkronisasi otomatis seluruh potongan absensi bulanan ke Penggajian (Payroll)
     */
    public static function syncMonthlyToPayroll(string $month, ?array $overrideSettings = null): array
    {
        $periodDetails = self::getMonthPeriodDetails($month);
        $settings = array_merge(self::getAttendanceSettings(), $overrideSettings ?? []);
        $monthEnd = $periodDetails['end_datetime'];

        $attendances = Attendance::with('employee')
            ->whereBetween('date', [$periodDetails['start_date'], $periodDetails['end_date']])
            ->get();

        $specialAtts = SpecialAttendance::with(['employee', 'team'])
            ->whereBetween('date', [$periodDetails['start_date'], $periodDetails['end_date']])
            ->get();

        $workSchedules = WorkSchedule::whereBetween('date', [$periodDetails['start_date'], $periodDetails['end_date']])
            ->where('status', 'work')
            ->get();

        $employees = Employee::with('area', 'shift')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->get();

        $recap = self::calculateMonthlyRecap($employees, $attendances, $periodDetails, $settings, $workSchedules);

        $syncedDeductionsCount = 0;
        $affectedEmployeesCount = 0;

        DB::transaction(function () use (
            $recap,
            $attendances,
            $specialAtts,
            $month,
            $monthEnd,
            &$syncedDeductionsCount,
            &$affectedEmployeesCount
        ) {
            foreach ($recap as $row) {
                $emp = Employee::find($row['employee_id']);
                if (!$emp || !$emp->email) continue;

                $user = User::where('email', $emp->email)->first();
                if (!$user) continue;

                $hasAnyPenalty = false;

                // 1. Potongan Terlambat
                if ($row['penalties']['late'] > 0) {
                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_LATE_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Terlambat Absensi Periode {$month} ({$row['total_late']}x telat, {$row['total_late_minutes']} menit)",
                            'amount' => $row['penalties']['late'],
                            'deduction_date' => $monthEnd->format('Y-m-d'),
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                }

                // 2. Potongan Lupa Pulang (Missing Out)
                if ($row['penalties']['missing_out'] > 0) {
                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_NO_OUT_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Lupa Absen Pulang Periode {$month} ({$row['total_missing_out']}x)",
                            'amount' => $row['penalties']['missing_out'],
                            'deduction_date' => $monthEnd->format('Y-m-d'),
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                }

                // 3. Potongan Pulang Cepat
                if ($row['penalties']['early_leave'] > 0) {
                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_EARLY_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Pulang Cepat Periode {$month} ({$row['total_early_leave']}x)",
                            'amount' => $row['penalties']['early_leave'],
                            'deduction_date' => $monthEnd->format('Y-m-d'),
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                }

                // 4. Potongan Alpha / Mangkir
                if ($row['penalties']['alpha'] > 0) {
                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_ALPHA_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Alpha / Mangkir Kerja Periode {$month} ({$row['total_alpha']} hari)",
                            'amount' => $row['penalties']['alpha'],
                            'deduction_date' => $monthEnd->format('Y-m-d'),
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                }

                if ($hasAnyPenalty) {
                    $affectedEmployeesCount++;
                }
            }

            // Tandai seluruh absensi dalam periode sebagai tersinkron
            Attendance::whereBetween('date', [$periodDetails['start_date'], $periodDetails['end_date']])
                ->update(['is_synced_to_payroll' => true]);

            SpecialAttendance::whereBetween('date', [$periodDetails['start_date'], $periodDetails['end_date']])
                ->update(['is_synced_to_payroll' => true]);
        });

        return [
            'success' => true,
            'period' => $periodDetails['period_label'],
            'month' => $month,
            'synced_deductions_count' => $syncedDeductionsCount,
            'affected_employees_count' => $affectedEmployeesCount,
        ];
    }
}

