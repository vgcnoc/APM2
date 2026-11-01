<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\SpecialAttendance;
use App\Models\SpecialTeam;
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
            'early_leave_tolerance' => self::resolveEarlyLeaveTolerance(),

            'alpha_enabled' => Setting::get('attendance_alpha_enabled', Setting::get('attendance_alpha_penalty_enabled', '1')) === '1',
            'alpha_amount' => (float) Setting::get('attendance_alpha_amount', 35000),

            'checkout_auto_lock' => Setting::get('attendance_checkout_auto_lock', '1') === '1',
            'checkout_deadline_mode' => Setting::get('attendance_checkout_deadline_mode', 'shift_relative'),
            'checkout_deadline_time' => self::normalizeTimeStr(Setting::get('attendance_checkout_deadline_time', '23:30'), '23:30'),
            'checkout_deadline_hours' => max(1, (int) Setting::get('attendance_checkout_deadline_hours', 4)),
            'checkout_min_work_hours' => max(0, (int) Setting::get('attendance_checkout_min_work_hours', 4)),
            'special_deadline_time' => self::normalizeTimeStr(Setting::get('attendance_special_deadline_time', '23:30'), '23:30'),
            'cron_enabled' => Setting::get('attendance_cron_enabled', '1') === '1',
        ];
    }

    /**
     * Normalisasi string jam ke format HH:MM yang valid
     */
    public static function normalizeTimeStr(?string $time, string $fallback = '23:30'): string
    {
        if (!$time) {
            return $fallback;
        }

        $trimmed = trim($time);
        if (preg_match('/^(\d{1,2}):(\d{2})/', $trimmed, $m)) {
            $h = max(0, min(23, (int) $m[1]));
            $min = max(0, min(59, (int) $m[2]));
            return sprintf('%02d:%02d', $h, $min);
        }

        return $fallback;
    }

    /**
     * Toleransi pulang lebih awal (dalam menit)
     */
    public static function resolveEarlyLeaveTolerance(): int
    {
        $checkoutTol = (int) Setting::get('attendance_checkout_early_tolerance', 15);
        $deductionTol = (int) Setting::get('attendance_early_leave_tolerance', 0);

        return max(0, $deductionTol > 0 ? $deductionTol : $checkoutTol);
    }

    /**
     * Hitung rentang waktu mulai & selesai shift untuk tanggal presensi tertentu (mendukung lintas hari / shift malam)
     */
    public static function resolveShiftWindow(string $dateStr, ?Shift $shift, string $tz = 'Asia/Jakarta'): ?array
    {
        if (!$shift || !$shift->start_time || !$shift->end_time) {
            return null;
        }

        $cleanDate = substr($dateStr, 0, 10);
        $startStr = self::normalizeTimeStr($shift->start_time, '08:00');
        $endStr = self::normalizeTimeStr($shift->end_time, '17:00');

        $start = Carbon::createFromFormat('Y-m-d H:i', "{$cleanDate} {$startStr}", $tz)->startOfMinute();
        $end = Carbon::createFromFormat('Y-m-d H:i', "{$cleanDate} {$endStr}", $tz)->startOfMinute();

        $isCrossMidnight = $shift->isCrossMidnight();
        if ($isCrossMidnight) {
            $end->addDay();
        }

        return [
            'start' => $start,
            'end' => $end,
            'is_cross_midnight' => $isCrossMidnight,
        ];
    }

    /**
     * Resolusi jam paling awal seorang karyawan diizinkan melakukan absen masuk.
     * Menggunakan jam mulai shift dikurangi batas waktu absen lebih awal (default: 60 menit / 1 jam).
     */
    public static function resolveEarliestClockInTime(
        string $dateStr,
        ?Shift $shift,
        ?int $earlyLimitMinutes = null,
        string $tz = 'Asia/Jakarta'
    ): ?Carbon {
        if (!$shift || empty($shift->start_time)) {
            return null;
        }

        $window = self::resolveShiftWindow($dateStr, $shift, $tz);
        if (!$window || empty($window['start'])) {
            return null;
        }

        if ($earlyLimitMinutes === null) {
            $earlyLimitMinutes = max(1, (int) Setting::get('attendance_clock_in_early_limit_minutes', 60));
        }

        return $window['start']->copy()->subMinutes($earlyLimitMinutes);
    }

    /**
     * Cek apakah jendela absensi masuk (Clock-In Window) sudah dibuka atau masih terkunci karena terlalu awal.
     *
     * Mendukung:
     * - Shift reguler (pagi, siang, sore)
     * - Shift malam / lintas hari (cross midnight)
     * - Konfigurasi fleksibel menit sebelum shift (default 60 menit / 1 jam)
     */
    public static function isClockInWindowOpen(
        string $dateStr,
        ?Shift $shift,
        ?Carbon $now = null,
        ?int $earlyLimitMinutes = null,
        ?bool $limitEnabled = null,
        string $tz = 'Asia/Jakarta'
    ): array {
        $now = $now ? $now->copy()->setTimezone($tz) : Carbon::now($tz);

        if ($limitEnabled === null) {
            $limitEnabled = Setting::get('attendance_clock_in_early_limit_enabled', '1') === '1';
        }

        if ($earlyLimitMinutes === null) {
            $earlyLimitMinutes = max(1, (int) Setting::get('attendance_clock_in_early_limit_minutes', 60));
        }

        if (!$limitEnabled || !$shift || empty($shift->start_time)) {
            return [
                'is_restricted' => false,
                'is_open' => true,
                'shift_start' => null,
                'shift_end' => null,
                'earliest_time' => null,
                'minutes_until_open' => 0,
                'early_limit_minutes' => $earlyLimitMinutes,
                'limit_enabled' => $limitEnabled,
            ];
        }

        $window = self::resolveShiftWindow($dateStr, $shift, $tz);
        if (!$window || empty($window['start'])) {
            return [
                'is_restricted' => false,
                'is_open' => true,
                'shift_start' => null,
                'shift_end' => null,
                'earliest_time' => null,
                'minutes_until_open' => 0,
                'early_limit_minutes' => $earlyLimitMinutes,
                'limit_enabled' => $limitEnabled,
            ];
        }

        $shiftStart = $window['start'];
        $earliestTime = $shiftStart->copy()->subMinutes($earlyLimitMinutes);

        // Jika waktu sekarang masih sebelum waktu paling awal absen dibuka
        if ($now->lessThan($earliestTime)) {
            $diffMinutes = (int) ceil($now->diffInRealMinutes($earliestTime, false));
            return [
                'is_restricted' => true,
                'is_open' => false,
                'shift_start' => $shiftStart,
                'shift_end' => $window['end'],
                'earliest_time' => $earliestTime,
                'minutes_until_open' => max(1, $diffMinutes),
                'early_limit_minutes' => $earlyLimitMinutes,
                'limit_enabled' => true,
            ];
        }

        return [
            'is_restricted' => true,
            'is_open' => true,
            'shift_start' => $shiftStart,
            'shift_end' => $window['end'],
            'earliest_time' => $earliestTime,
            'minutes_until_open' => 0,
            'early_limit_minutes' => $earlyLimitMinutes,
            'limit_enabled' => true,
        ];
    }

    /**
     * Evaluasi apakah jendela waktu absen pulang (clock-out) sudah dibuka.
     * Karyawan tidak boleh melakukan absen pulang mendahului jam selesai shift jika proteksi diaktifkan.
     */
    public static function isClockOutWindowOpen(
        string $dateStr,
        ?Shift $shift,
        ?Carbon $now = null,
        ?int $earlyToleranceMinutes = null,
        ?bool $limitEnabled = null,
        string $tz = 'Asia/Jakarta'
    ): array {
        $now = $now ? $now->copy()->setTimezone($tz) : Carbon::now($tz);

        if ($limitEnabled === null) {
            $limitEnabled = Setting::get('attendance_clock_out_early_restriction_enabled', '1') === '1';
        }

        if ($earlyToleranceMinutes === null) {
            $earlyToleranceMinutes = (int) Setting::get('attendance_clock_out_early_tolerance_minutes', 0);
        }

        if (!$limitEnabled || !$shift || empty($shift->end_time)) {
            return [
                'is_restricted' => false,
                'is_open' => true,
                'shift_start' => null,
                'shift_end' => null,
                'earliest_time' => null,
                'minutes_until_open' => 0,
                'early_tolerance_minutes' => $earlyToleranceMinutes,
                'limit_enabled' => $limitEnabled,
            ];
        }

        $window = self::resolveShiftWindow($dateStr, $shift, $tz);
        if (!$window || empty($window['end'])) {
            return [
                'is_restricted' => false,
                'is_open' => true,
                'shift_start' => null,
                'shift_end' => null,
                'earliest_time' => null,
                'minutes_until_open' => 0,
                'early_tolerance_minutes' => $earlyToleranceMinutes,
                'limit_enabled' => $limitEnabled,
            ];
        }

        $shiftEnd = $window['end'];
        $earliestTime = $shiftEnd->copy()->subMinutes(max(0, $earlyToleranceMinutes));

        // Jika waktu sekarang masih sebelum waktu paling awal absen pulang dibuka
        if ($now->lessThan($earliestTime)) {
            $diffMinutes = (int) ceil($now->diffInRealMinutes($earliestTime, false));
            return [
                'is_restricted' => true,
                'is_open' => false,
                'shift_start' => $window['start'],
                'shift_end' => $shiftEnd,
                'earliest_time' => $earliestTime,
                'minutes_until_open' => max(1, $diffMinutes),
                'early_tolerance_minutes' => $earlyToleranceMinutes,
                'limit_enabled' => true,
            ];
        }

        return [
            'is_restricted' => true,
            'is_open' => true,
            'shift_start' => $window['start'],
            'shift_end' => $shiftEnd,
            'earliest_time' => $earliestTime,
            'minutes_until_open' => 0,
            'early_tolerance_minutes' => $earlyToleranceMinutes,
            'limit_enabled' => true,
        ];
    }

    /**
     * Resolusi timestamp lengkap jam masuk (clock_in), termasuk bila karyawan shift malam absen masuk lewat pukul 00:00
     */
    public static function resolveClockInDateTime(Attendance $att, ?Shift $shift = null, string $tz = 'Asia/Jakarta'): ?Carbon
    {
        if (empty($att->clock_in)) {
            return null;
        }

        $dateStr = is_string($att->date) ? substr($att->date, 0, 10) : $att->date->format('Y-m-d');
        $sameDayClockIn = Carbon::parse("{$dateStr} {$att->clock_in}", $tz);

        $resolvedShift = $shift ?? ($att->relationLoaded('shift') ? $att->shift : ($att->shift_id ? Shift::find($att->shift_id) : null));
        if ($resolvedShift && $resolvedShift->isCrossMidnight()) {
            $window = self::resolveShiftWindow($dateStr, $resolvedShift, $tz);
            if ($window) {
                $nextDayClockIn = $sameDayClockIn->copy()->addDay();
                $diffSame = abs($sameDayClockIn->diffInMinutes($window['start']));
                $diffNext = abs($nextDayClockIn->diffInMinutes($window['start']));
                if ($diffNext < $diffSame) {
                    return $nextDayClockIn;
                }
            }
        }

        return $sameDayClockIn;
    }

    /**
     * Hitung batas waktu terakhir absen pulang (Clock-Out Cut-Off) berdasarkan tanggal & shift kerja.
     *
     * - Mode 'shift_relative': Jam Selesai Shift + N Jam (mendukung lintas hari / shift malam).
     * - Mode 'before_cron' / 'fixed_time': Menggunakan jam cut-off pengaturan untuk shift pagi/reguler,
     *   dan otomatis menyesuaikan ke (Jam Selesai Shift + N Jam) apabila shift berakhir mendekati (<30 menit)
     *   atau melewati jam cut-off (contoh: Shift 2 sampai 21:00/23:00 atau Shift Malam 23:00-08:00).
     */
    public static function resolveCheckoutDeadlineForShift(
        string $dateStr,
        ?Shift $shift = null,
        ?array $settings = null,
        string $tz = 'Asia/Jakarta'
    ): Carbon {
        $cleanDate = substr($dateStr, 0, 10);
        $mode = $settings['checkout_deadline_mode'] ?? Setting::get('attendance_checkout_deadline_mode', 'shift_relative');
        $deadlineTime = self::normalizeTimeStr(
            $settings['checkout_deadline_time'] ?? Setting::get('attendance_checkout_deadline_time', '23:30'),
            '23:30'
        );
        $deadlineHours = max(1, (int) ($settings['checkout_deadline_hours'] ?? Setting::get('attendance_checkout_deadline_hours', 4)));

        $baseStaticDeadline = Carbon::createFromFormat('Y-m-d H:i', "{$cleanDate} {$deadlineTime}", $tz)->endOfMinute();

        $window = self::resolveShiftWindow($cleanDate, $shift, $tz);
        if (!$window) {
            return $baseStaticDeadline;
        }

        $shiftEnd = $window['end'];
        $shiftRelativeDeadline = $shiftEnd->copy()->addHours($deadlineHours)->endOfMinute();

        if ($mode === 'shift_relative') {
            return $shiftRelativeDeadline;
        }

        // Proteksi cerdas Shift Sore & Shift Malam pada mode 'before_cron' / 'fixed_time':
        // Jika jadwal selesai shift melewati (atau sisa < 30 menit dari) jam cut-off statis, berikan +N jam dari jam selesai shift.
        if ($shiftEnd->copy()->addMinutes(30)->greaterThan($baseStaticDeadline)) {
            return $shiftRelativeDeadline;
        }

        return $baseStaticDeadline;
    }

    /**
     * Hitung batas waktu terakhir absen pulang untuk record Attendance tertentu
     */
    public static function resolveCheckoutDeadline(
        Attendance $att,
        ?array $settings = null,
        ?Collection $shiftsById = null,
        string $tz = 'Asia/Jakarta'
    ): Carbon {
        $dateStr = is_string($att->date) ? substr($att->date, 0, 10) : $att->date->format('Y-m-d');

        $shift = null;
        if ($att->relationLoaded('shift')) {
            $shift = $att->shift;
        } elseif ($att->shift_id) {
            $shift = $shiftsById ? $shiftsById->get($att->shift_id) : Shift::find($att->shift_id);
        }

        return self::resolveCheckoutDeadlineForShift($dateStr, $shift, $settings, $tz);
    }

    /**
     * Cek apakah suatu record Attendance Reguler sudah berstatus Lupa Absen Pulang (Missing Clock-Out)
     */
    public static function isMissingClockOut(
        Attendance $att,
        ?Carbon $now = null,
        ?array $settings = null,
        ?Collection $shiftsById = null,
        string $tz = 'Asia/Jakarta'
    ): bool {
        if (empty($att->clock_in) || !empty($att->clock_out)) {
            return false;
        }

        $now = $now ?? Carbon::now($tz);
        $deadline = self::resolveCheckoutDeadline($att, $settings, $shiftsById, $tz);

        return $now->greaterThan($deadline);
    }

    /**
     * Hitung batas waktu terakhir absen pulang untuk Grup Tim Khusus
     */
    public static function resolveSpecialCheckoutDeadline(
        string $dateStr,
        ?SpecialTeam $team = null,
        ?string $specialDeadlineTime = null,
        string $tz = 'Asia/Jakarta'
    ): Carbon {
        $cleanDate = substr($dateStr, 0, 10);
        $deadlineTime = self::normalizeTimeStr(
            $specialDeadlineTime ?? Setting::get('attendance_special_deadline_time', Setting::get('attendance_checkout_deadline_time', '23:30')),
            '23:30'
        );

        $baseDeadline = Carbon::createFromFormat('Y-m-d H:i', "{$cleanDate} {$deadlineTime}", $tz)->endOfMinute();

        if ($team && $team->start_time && $team->end_time) {
            $startStr = self::normalizeTimeStr($team->start_time, '08:30');
            $endStr = self::normalizeTimeStr($team->end_time, '18:00');
            $teamEnd = Carbon::createFromFormat('Y-m-d H:i', "{$cleanDate} {$endStr}", $tz)->startOfMinute();
            if ($endStr < $startStr) {
                $teamEnd->addDay();
            }

            $mode = Setting::get('attendance_checkout_deadline_mode', 'shift_relative');
            $graceHours = max(1, (int) Setting::get('attendance_checkout_deadline_hours', 4));
            $shiftRelative = $teamEnd->copy()->addHours($graceHours)->endOfMinute();

            if ($mode === 'shift_relative') {
                return $shiftRelative->greaterThan($baseDeadline) ? $shiftRelative : $baseDeadline;
            }

            if ($teamEnd->copy()->addMinutes(30)->greaterThan($baseDeadline)) {
                return $shiftRelative;
            }
        }

        return $baseDeadline;
    }

    /**
     * Cek apakah suatu record SpecialAttendance sudah berstatus Tanpa Absen Pulang
     */
    public static function isSpecialMissingClockOut(
        SpecialAttendance $att,
        ?SpecialTeam $team = null,
        ?Carbon $now = null,
        ?string $specialDeadlineTime = null,
        string $tz = 'Asia/Jakarta'
    ): bool {
        if (empty($att->clock_in) || !empty($att->clock_out)) {
            return false;
        }

        $now = $now ?? Carbon::now($tz);
        $dateStr = is_string($att->date) ? substr($att->date, 0, 10) : $att->date->format('Y-m-d');
        $resolvedTeam = $team ?? ($att->relationLoaded('team') ? $att->team : ($att->special_team_id ? SpecialTeam::find($att->special_team_id) : null));
        $deadline = self::resolveSpecialCheckoutDeadline($dateStr, $resolvedTeam, $specialDeadlineTime, $tz);

        return $now->greaterThan($deadline);
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
        $now = Carbon::now('Asia/Jakarta');
        $today = Carbon::today('Asia/Jakarta');
        $todayDate = $today->format('Y-m-d');
        $elapsedWorkDays = $periodDetails['elapsed_work_days'];
        $totalWorkDays = $periodDetails['total_work_days'];
        $shiftsById = Shift::all()->keyBy('id');

        return $employees->map(function ($emp) use (
            $attendances,
            $workSchedules,
            $settings,
            $now,
            $today,
            $todayDate,
            $shiftsById,
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

            // 4. Lupa Clock-Out (Missing Out) - berbasis batas pulang masing-masing shift (mendukung shift sore & lintas hari)
            $missingOutAtts = $empAtts->filter(function ($a) use ($now, $settings, $shiftsById) {
                return self::isMissingClockOut($a, $now, $settings, $shiftsById, 'Asia/Jakarta');
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
                        ->filter(function ($s) use ($todayDate, $now, $shiftsById) {
                            $sDate = is_string($s->date) ? substr($s->date, 0, 10) : $s->date->format('Y-m-d');
                            if ($sDate < $todayDate) {
                                return true;
                            }
                            if ($sDate === $todayDate) {
                                $schedShift = $s->shift_id ? $shiftsById->get($s->shift_id) : null;
                                if ($schedShift && $schedShift->start_time) {
                                    $win = self::resolveShiftWindow($sDate, $schedShift, 'Asia/Jakarta');
                                    if ($win && $now->lessThan($win['start'])) {
                                        return false;
                                    }
                                }
                                return true;
                            }
                            return false;
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

        $attendances = Attendance::with(['employee', 'shift'])
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

