<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Payroll;
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

        $nowCalc = Carbon::now('Asia/Jakarta');
        $cutoffTimeCalc = Setting::get('attendance_checkout_deadline_time', '23:30');
        $isTodayClosedCalc = $nowCalc->format('H:i') >= $cutoffTimeCalc;

        // Batas hari kerja yang telah selesai dievaluasi:
        // Jika bulan berjalan: jika jam sekarang belum melewati batas checkout malam (23:30), maka hari ini masih berlangsung sehingga cutoff adalah kemarin.
        $effectiveCutoffDate = $isCurrentMonth 
            ? ($isTodayClosedCalc ? $today->copy() : $today->copy()->subDay()) 
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
            'is_today_closed' => $isTodayClosedCalc,
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

            'sick_no_cert_enabled' => Setting::get('attendance_sick_no_cert_enabled', '1') === '1',
            'sick_no_cert_amount' => (float) Setting::get('attendance_sick_no_cert_amount', 20000),

            'permit_enabled' => Setting::get('attendance_permit_enabled', '1') === '1',
            'permit_amount' => (float) Setting::get('attendance_permit_amount', 25000),

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
     * Resolusi shift kerja untuk karyawan pada tanggal tertentu.
     * Mengutamakan shift pada catatan absensi, jadwal kerja (WorkSchedule), shift default karyawan, lalu shift fallback.
     */
    public static function resolveEmployeeShiftForDate(
        Employee $employee,
        string $dateStr,
        ?Shift $attendanceShift = null,
        ?Collection $empSchedules = null,
        ?Collection $shiftsById = null
    ): ?Shift {
        if ($attendanceShift) {
            return $attendanceShift;
        }

        $cleanDate = substr($dateStr, 0, 10);

        if ($empSchedules && $empSchedules->isNotEmpty()) {
            $schedule = $empSchedules->first(function ($s) use ($cleanDate) {
                $d = is_string($s->date) ? substr($s->date, 0, 10) : $s->date->format('Y-m-d');
                return $d === $cleanDate;
            });
            if ($schedule && $schedule->status !== 'off' && !empty($schedule->shift_id)) {
                if ($schedule->relationLoaded('shift') && $schedule->shift) {
                    return $schedule->shift;
                }
                return $shiftsById ? $shiftsById->get($schedule->shift_id) : Shift::find($schedule->shift_id);
            }
        }

        if ($employee->relationLoaded('shift') && $employee->shift) {
            return $employee->shift;
        }
        if (!empty($employee->shift_id)) {
            return $shiftsById ? $shiftsById->get($employee->shift_id) : Shift::find($employee->shift_id);
        }

        return null;
    }

    /**
     * Hitung target hari kerja individu secara adil dan dinamis.
     * Memperhitungkan:
     * - Tanggal bergabung (join_date) & tanggal keluar (resign_date) karyawan
     * - Roster jadwal kerja khusus (WorkSchedule) per tanggal (status: work vs off)
     * - Hari libur terjadwal (OFF) TIDAK DIHITUNG sebagai kewajiban kerja / bukan alpha
     * - Standar operasional default jika tanpa roster (Senin - Sabtu, Minggu off)
     */
    public static function calculateEmployeeTargetDays(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $asOfDate,
        ?Collection $empSchedules = null,
        ?array $presentDates = null,
        ?Collection $shiftsById = null,
        ?Carbon $now = null
    ): array {
        $tz = 'Asia/Jakarta';
        $now = $now ? $now->copy()->setTimezone($tz) : Carbon::now($tz);
        $todayStr = Carbon::today($tz)->format('Y-m-d');

        /**
         * Helper cerdas penentu apakah suatu hari kerja sudah jatuh tempo wajib hadir:
         * - Hari sebelum hari ini: Sudah pasti jatuh tempo (harinya sudah lewat).
         * - Hari ini: Jika sudah absen, maka dihitung hadir & sah.
         *   Jika belum absen: dicek apakah shift hari ini sudah selesai atau belum.
         *   Jika shift hari ini BELUM selesai (belum mulai atau sedang berlangsung),
         *   karyawan TIDAK BOLEH dianggap mangkir/alpha secara prematur!
         * - Hari setelah hari ini (masa depan): Belum jatuh tempo.
         */
        $isWorkDayDue = function (string $dStr, ?Shift $shift = null) use ($now, $todayStr, $presentDates, $tz) {
            if ($dStr < $todayStr) {
                return true;
            }

            if ($dStr === $todayStr) {
                // Jika sudah absen hari ini, maka hari ini sah terhitung hari kerja aktif
                if (!empty($presentDates[$dStr])) {
                    return true;
                }

                // Jika belum absen hari ini, cek jendela shift
                if ($shift) {
                    $window = self::resolveShiftWindow($dStr, $shift, $tz);
                    if ($window && !empty($window['end'])) {
                        // Jika jam sekarang MASIH sebelum jam selesai shift, shift belum berakhir
                        if ($now->lessThan($window['end'])) {
                            return false;
                        }
                    }
                } else {
                    // Shift standar: sebelum jam 17:00 belum dianggap selesai
                    if ($now->hour < 17) {
                        return false;
                    }
                }

                return true;
            }

            return false;
        };

        $effectiveStart = $startDate->copy();
        if ($employee->join_date) {
            $join = Carbon::parse($employee->join_date, $tz)->startOfDay();
            if ($join->isAfter($effectiveStart)) {
                $effectiveStart = $join;
            }
        }

        $effectiveEnd = $endDate->copy();
        if ($employee->status === 'resigned' && $employee->resign_date) {
            $resign = Carbon::parse($employee->resign_date, $tz)->endOfDay();
            if ($resign->isBefore($effectiveEnd)) {
                $effectiveEnd = $resign;
            }
        }

        $effectiveAsOf = $asOfDate->copy();
        if ($effectiveEnd->isBefore($effectiveAsOf)) {
            $effectiveAsOf = $effectiveEnd;
        }

        // Jika karyawan belum bergabung di periode ini
        if ($effectiveStart->isAfter($effectiveAsOf)) {
            return [
                'target_days' => 0,
                'total_scheduled_work_days' => 0,
                'scheduled_off_days' => 0,
                'scheduled_leave_days' => 0,
                'scheduled_sick_days' => 0,
                'is_schedule_custom' => false,
            ];
        }

        // Cek apakah karyawan memiliki jadwal khusus di tabel work_schedules
        $hasCustomSchedules = $empSchedules && $empSchedules->isNotEmpty();

        if ($hasCustomSchedules) {
            $workCount = 0;
            $offCount = 0;
            $leaveCount = 0;
            $sickCount = 0;

            $schedulesMap = $empSchedules->keyBy(function ($s) {
                return is_string($s->date) ? substr($s->date, 0, 10) : $s->date->format('Y-m-d');
            });

            $hasExplicitWorkRows = $empSchedules->where('status', 'work')->count() > 0;

            if ($hasExplicitWorkRows) {
                // Mode Roster Penuh: Target adalah seluruh tanggal berstatus 'work' yang sudah jatuh tempo
                $cursor = $effectiveStart->copy()->startOfDay();
                while ($cursor->lte($effectiveAsOf)) {
                    $dStr = $cursor->format('Y-m-d');
                    $s = $schedulesMap->get($dStr);
                    if ($s) {
                        if ($s->status === 'work' && !empty($s->shift_id)) {
                            $shift = $s->relationLoaded('shift') && $s->shift
                                ? $s->shift
                                : ($shiftsById ? $shiftsById->get($s->shift_id) : Shift::find($s->shift_id));
                            if ($isWorkDayDue($dStr, $shift)) {
                                $workCount++;
                            }
                        } elseif ($s->status === 'off' || empty($s->shift_id)) {
                            $offCount++;
                        } elseif ($s->status === 'leave' || $s->status === 'permit') {
                            $leaveCount++;
                        } elseif ($s->status === 'sick') {
                            $sickCount++;
                        }
                    }
                    $cursor->addDay();
                }

                $targetDays = ($workCount === 0 && empty($presentDates)) ? 0 : max(1, $workCount);

                return [
                    'target_days' => $targetDays,
                    'total_scheduled_work_days' => $workCount,
                    'scheduled_off_days' => $offCount,
                    'scheduled_leave_days' => $leaveCount,
                    'scheduled_sick_days' => $sickCount,
                    'is_schedule_custom' => true,
                ];
            } else {
                // Mode Off-Days: Jadwal Senin-Sabtu dikurangi tanggal-tanggal OFF/Libur yang dijadwalkan
                $defaultDays = 0;
                $offDays = 0;

                $cursor = $effectiveStart->copy()->startOfDay();
                while ($cursor->lte($effectiveAsOf)) {
                    $dStr = $cursor->format('Y-m-d');
                    $s = $schedulesMap->get($dStr);

                    if ($s && ($s->status === 'off' || empty($s->shift_id))) {
                        $offDays++;
                    } elseif (!$cursor->isSunday()) {
                        $shift = self::resolveEmployeeShiftForDate($employee, $dStr, null, $empSchedules, $shiftsById);
                        if ($isWorkDayDue($dStr, $shift)) {
                            $defaultDays++;
                        }
                    }
                    $cursor->addDay();
                }

                $targetDays = ($defaultDays === 0 && empty($presentDates)) ? 0 : max(1, $defaultDays);

                return [
                    'target_days' => $targetDays,
                    'total_scheduled_work_days' => $defaultDays,
                    'scheduled_off_days' => $offDays,
                    'scheduled_leave_days' => 0,
                    'scheduled_sick_days' => 0,
                    'is_schedule_custom' => true,
                ];
            }
        }

        // Standar default tanpa WorkSchedule (Senin - Sabtu, Minggu off)
        $defaultDays = 0;
        $cursor = $effectiveStart->copy()->startOfDay();
        while ($cursor->lte($effectiveAsOf)) {
            $dStr = $cursor->format('Y-m-d');
            if (!$cursor->isSunday()) {
                $shift = self::resolveEmployeeShiftForDate($employee, $dStr, null, $empSchedules, $shiftsById);
                if ($isWorkDayDue($dStr, $shift)) {
                    $defaultDays++;
                }
            }
            $cursor->addDay();
        }

        $targetDays = ($defaultDays === 0 && empty($presentDates)) ? 0 : max(1, $defaultDays);

        return [
            'target_days' => $targetDays,
            'total_scheduled_work_days' => $defaultDays,
            'scheduled_off_days' => 0,
            'scheduled_leave_days' => 0,
            'scheduled_sick_days' => 0,
            'is_schedule_custom' => false,
        ];
    }

    /**
     * Hitung ketepatan waktu presensi karyawan relatif terhadap jam mulai shift kerja.
     * Adil untuk seluruh variasi shift (Shift Pagi, Siang, Sore, Malam/Lintas Hari).
     *
     * Nilai return:
     * - relative_early_minutes: Positif jika hadir lebih awal, negatif jika terlambat.
     *   Contoh: Shift 08:00 datang 07:45 -> +15 menit (lebih awal 15m).
     *   Contoh: Shift 13:00 datang 12:40 -> +20 menit (lebih awal 20m).
     */
    public static function evaluateAttendancePunctuality(
        Attendance $att,
        ?Shift $shift = null,
        ?array $settings = null,
        string $tz = 'Asia/Jakarta'
    ): array {
        if (empty($att->clock_in)) {
            return [
                'has_clock_in' => false,
                'clock_in_formatted' => '-',
                'clock_out_formatted' => '-',
                'relative_early_minutes' => 0,
                'is_late' => false,
                'late_minutes' => 0,
                'is_early_leave' => false,
                'early_leave_minutes' => 0,
                'shift_name' => $shift?->name ?: 'Standar',
                'shift_time' => $shift?->formatted_time ?: '-',
            ];
        }

        $dateStr = is_string($att->date) ? substr($att->date, 0, 10) : $att->date->format('Y-m-d');
        $resolvedShift = $shift ?: ($att->relationLoaded('shift') ? $att->shift : ($att->shift_id ? Shift::find($att->shift_id) : null));
        $window = self::resolveShiftWindow($dateStr, $resolvedShift, $tz);

        $clockInTime = self::resolveClockInDateTime($att, $resolvedShift, $tz);
        $clockInFormatted = $clockInTime ? $clockInTime->format('H:i') . ' WIB' : '-';

        $clockOutFormatted = '-';
        $isEarlyLeave = false;
        $earlyLeaveMinutes = (int) ($att->early_leave_minutes ?? 0);

        if (!empty($att->clock_out)) {
            $clockOutTime = Carbon::parse("{$dateStr} {$att->clock_out}", $tz);
            if ($window && $window['is_cross_midnight'] && $clockOutTime->lessThan($window['start'])) {
                $clockOutTime->addDay();
            }
            $clockOutFormatted = $clockOutTime->format('H:i') . ' WIB';

            if ($window && !empty($window['end'])) {
                $earlyTolerance = self::resolveEarlyLeaveTolerance();
                $threshold = $window['end']->copy()->subMinutes($earlyTolerance);
                if ($clockOutTime->lessThan($threshold)) {
                    $isEarlyLeave = true;
                    $calcEarlyMins = (int) max(0, $clockOutTime->diffInMinutes($window['end']));
                    $earlyLeaveMinutes = max($earlyLeaveMinutes, $calcEarlyMins);
                }
            }
        } elseif (str_contains(strtolower($att->notes ?? ''), 'pulang lebih awal')) {
            $isEarlyLeave = true;
        }

        $relativeEarlyMinutes = 0;
        $isLate = ($att->status === 'late') || ((int) ($att->late_minutes ?? 0) > 0);
        $lateMinutes = (int) ($att->late_minutes ?? 0);

        if ($window && !empty($window['start']) && $clockInTime) {
            // Selisih menit antara jam masuk dengan jam mulai shift
            // Jika clock_in SEBELUM shift start -> nilai positif (hadir lebih awal)
            // Jika clock_in SESUDAH shift start -> nilai negatif (terlambat)
            $diffMinutes = (int) $clockInTime->diffInMinutes($window['start'], false);
            $relativeEarlyMinutes = $diffMinutes;

            // Jika telat tapi kolom late_minutes belum terisi
            if ($diffMinutes < 0) {
                $lateTol = (int) Setting::get('attendance_late_tolerance', 15);
                if (abs($diffMinutes) > $lateTol) {
                    $isLate = true;
                    $lateMinutes = max($lateMinutes, abs($diffMinutes));
                }
            }
        }

        return [
            'has_clock_in' => true,
            'clock_in_time' => $clockInTime,
            'clock_in_formatted' => $clockInFormatted,
            'clock_out_formatted' => $clockOutFormatted,
            'relative_early_minutes' => $relativeEarlyMinutes,
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'is_early_leave' => $isEarlyLeave,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'shift_name' => $resolvedShift?->name ?: 'Standar',
            'shift_time' => $resolvedShift?->formatted_time ?: '-',
        ];
    }

    /**
     * Hitung performa dan skor Absen Terbaik per karyawan secara adil, transparan, dan cerdas.
     * Memperhitungkan variasi jam kerja (shift) dan hari kerja (roster) masing-masing karyawan.
     */
    public static function calculateEmployeeBestAttendance(
        Employee $employee,
        Collection $empAtts,
        Collection $empSchedules,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $asOfDate,
        ?Collection $empSpecials = null,
        ?Collection $empDuties = null,
        ?Collection $shiftsById = null,
        ?array $settings = null
    ): array {
        $shiftsById = $shiftsById ?: Shift::all()->keyBy('id');
        $settings = $settings ?: self::getAttendanceSettings();

        // 1. Kumpulkan tanggal kehadiran unik (Reguler + Tim Khusus + Dinas Luar)
        $presentDates = [];
        $punctualityOffsets = []; // Menit hadir lebih awal/telat relatif terhadap shift
        $rawClockInSecs = [];
        $clockOutSecs = [];
        $totalLates = 0;
        $totalLateMinutes = 0;
        $totalEarlyLeaves = 0;
        $totalEarlyMinutes = 0;
        $extraOffDayPresents = 0;

        // Jadwal kerja map
        $schedulesMap = $empSchedules->keyBy(function ($s) {
            return is_string($s->date) ? substr($s->date, 0, 10) : $s->date->format('Y-m-d');
        });

        foreach ($empAtts as $att) {
            if (empty($att->clock_in)) continue;

            $dStr = is_string($att->date) ? substr($att->date, 0, 10) : $att->date->format('Y-m-d');
            $presentDates[$dStr] = true;

            // Resolusi shift kerja untuk tanggal ini
            $resolvedShift = self::resolveEmployeeShiftForDate($employee, $dStr, $att->shift, $empSchedules, $shiftsById);
            $punc = self::evaluateAttendancePunctuality($att, $resolvedShift, $settings);

            if ($punc['is_late']) {
                $totalLates++;
                $totalLateMinutes += $punc['late_minutes'];
            }
            if ($punc['is_early_leave']) {
                $totalEarlyLeaves++;
                $totalEarlyMinutes += $punc['early_leave_minutes'];
            }

            // Punctuality offset (clamped to [-60, +60] agar tidak ada pencilan ekstrim)
            $clampedEarly = max(-60, min(60, $punc['relative_early_minutes']));
            $punctualityOffsets[] = $clampedEarly;

            // Jam masuk & pulang untuk display
            $cIn = Carbon::parse("{$dStr} {$att->clock_in}");
            $rawClockInSecs[] = ($cIn->hour * 3600) + ($cIn->minute * 60) + $cIn->second;

            if (!empty($att->clock_out)) {
                $cOut = Carbon::parse("{$dStr} {$att->clock_out}");
                $clockOutSecs[] = ($cOut->hour * 3600) + ($cOut->minute * 60) + $cOut->second;
            }

            // Cek apakah hadir di hari OFF / Libur yang dijadwalkan
            $sched = $schedulesMap->get($dStr);
            $cDate = Carbon::parse($dStr);
            if ($sched && ($sched->status === 'off' || empty($sched->shift_id))) {
                $extraOffDayPresents++;
            } elseif (!$sched && $cDate->isSunday()) {
                $extraOffDayPresents++;
            }
        }

        if ($empSpecials && $empSpecials->isNotEmpty()) {
            foreach ($empSpecials as $sp) {
                if (empty($sp->clock_in)) continue;
                $dStr = is_string($sp->date) ? substr($sp->date, 0, 10) : $sp->date->format('Y-m-d');
                $presentDates[$dStr] = true;
                if ($sp->status === 'late') {
                    $totalLates++;
                    $totalLateMinutes += (int) ($sp->late_minutes ?? 0);
                }
            }
        }

        if ($empDuties && $empDuties->isNotEmpty()) {
            foreach ($empDuties as $fd) {
                if (empty($fd->check_in_time)) continue;
                $dStr = is_string($fd->duty_date) ? substr($fd->duty_date, 0, 10) : $fd->duty_date->format('Y-m-d');
                $presentDates[$dStr] = true;
                $cIn = Carbon::parse($fd->check_in_time);
                $rawClockInSecs[] = ($cIn->hour * 3600) + ($cIn->minute * 60) + $cIn->second;
                if (!empty($fd->check_out_time)) {
                    $cOut = Carbon::parse($fd->check_out_time);
                    $clockOutSecs[] = ($cOut->hour * 3600) + ($cOut->minute * 60) + $cOut->second;
                }
            }
        }

        // 2. Target hari kerja individu dinamis (cerdas, memperhitungkan jatuh tempo shift hari ini)
        $targetInfo = self::calculateEmployeeTargetDays(
            $employee,
            $startDate,
            $endDate,
            $asOfDate,
            $empSchedules,
            $presentDates,
            $shiftsById
        );
        $targetDays = $targetInfo['target_days'];

        $presents = count($presentDates);

        // Alpha / Mangkir Kerja:
        // Evaluasi hanya pada hari kerja yang sudah lewat di mana karyawan TIDAK hadir dan TIDAK memiliki izin/sakit
        $alphas = 0;
        if ($targetDays > $presents) {
            $missedDays = $targetDays - $presents;
            // Kurangi dengan jadwal izin / sakit yang sah
            $excusedDays = ($targetInfo['scheduled_leave_days'] ?? 0) + ($targetInfo['scheduled_sick_days'] ?? 0);
            $alphas = max(0, $missedDays - $excusedDays);
        }

        // Rata-rata jam masuk & pulang (untuk display)
        $avgClockInDisplaySec = !empty($rawClockInSecs) ? (array_sum($rawClockInSecs) / count($rawClockInSecs)) : null;
        $avgClockInFormatted = $avgClockInDisplaySec !== null
            ? sprintf("%02d:%02d WIB", floor($avgClockInDisplaySec / 3600), floor(($avgClockInDisplaySec % 3600) / 60))
            : '-';

        $avgClockOutSec = !empty($clockOutSecs) ? (array_sum($clockOutSecs) / count($clockOutSecs)) : null;
        $avgClockOutFormatted = $avgClockOutSec !== null
            ? sprintf("%02d:%02d WIB", floor($avgClockOutSec / 3600), floor(($avgClockOutSec % 3600) / 60))
            : '-';

        $todayStr = Carbon::today('Asia/Jakarta')->format('Y-m-d');
        $hasActiveAttendanceToday = $empAtts->contains(function ($a) use ($todayStr) {
            $d = is_string($a->date) ? substr($a->date, 0, 10) : $a->date->format('Y-m-d');
            return $d === $todayStr && !empty($a->clock_in) && empty($a->clock_out);
        });
        if ($avgClockOutFormatted === '-' && $hasActiveAttendanceToday) {
            $avgClockOutFormatted = 'Belum Pulang';
        }

        // Rata-rata kedisiplinan hadir lebih awal relatif terhadap shift (dalam menit)
        $avgRelativeEarlyMinutes = !empty($punctualityOffsets) ? round(array_sum($punctualityOffsets) / count($punctualityOffsets), 1) : 0;

        // Label deskriptif ketepatan waktu
        $punctualityLabel = '-';
        if ($presents > 0) {
            if ($avgRelativeEarlyMinutes > 0) {
                $punctualityLabel = "+{$avgRelativeEarlyMinutes}m sebelum shift";
            } elseif ($avgRelativeEarlyMinutes === 0.0) {
                $punctualityLabel = "Tepat Waktu";
            } else {
                $punctualityLabel = "Telat avg " . abs($avgRelativeEarlyMinutes) . "m";
            }
        }

        // ==============================================================
        // FORMULA SKOR ABSEN TERBAIK YANG ADIL & CERDAS:
        // ==============================================================
        // 1. Base Kehadiran: (Presents / TargetDays) * 100 (Maks 100 poin)
        // 2. Bonus Loyalitas: Kehadiran saat hari OFF/Libur terjadwal (+0.5 poin/hari, maks +5 poin)
        // 3. Penalti Terlambat: -(Telat * 0.25) - (Menit * 0.01)
        // 4. Penalti Pulang Cepat: -(Pulang Cepat * 0.5)
        // 5. Penalti Alpha: -(Alpha * 2.0)
        if ($presents === 0) {
            $finalScore = 0;
            $baseAttendanceRate = 0;
            $bonusScore = 0;
        } else {
            $baseAttendanceRate = ($targetDays > 0) ? min(100.0, ($presents / $targetDays) * 100) : 0;
            $bonusScore = min(5.0, $extraOffDayPresents * 0.5);

            $penaltyLate = ($totalLates * 0.25) + ($totalLateMinutes * 0.01);
            $penaltyEarly = ($totalEarlyLeaves * 0.5);
            $penaltyAlpha = ($alphas * 2.0);

            $finalScore = max(0.0, round($baseAttendanceRate + $bonusScore - $penaltyLate - $penaltyEarly - $penaltyAlpha, 2));
        }

        // Nama shift representatif karyawan:
        // Cerdas mengutamakan shift aktif hari ini dari roster, absensi terbaru periode ini, atau fallback default
        $representativeShift = null;

        if ($empSchedules && $empSchedules->isNotEmpty()) {
            $todaySched = $empSchedules->first(function ($s) use ($todayStr) {
                $d = is_string($s->date) ? substr($s->date, 0, 10) : $s->date->format('Y-m-d');
                return $d === $todayStr && $s->status === 'work' && !empty($s->shift_id);
            });
            if ($todaySched) {
                $representativeShift = $todaySched->relationLoaded('shift') && $todaySched->shift
                    ? $todaySched->shift
                    : ($shiftsById ? $shiftsById->get($todaySched->shift_id) : Shift::find($todaySched->shift_id));
            }
        }

        if (!$representativeShift && $empAtts && $empAtts->isNotEmpty()) {
            $latestAtt = $empAtts->sortByDesc('date')->first();
            if ($latestAtt && $latestAtt->shift_id) {
                $representativeShift = $latestAtt->relationLoaded('shift') && $latestAtt->shift
                    ? $latestAtt->shift
                    : ($shiftsById ? $shiftsById->get($latestAtt->shift_id) : Shift::find($latestAtt->shift_id));
            }
        }

        if (!$representativeShift && $empSchedules && $empSchedules->isNotEmpty()) {
            $latestSched = $empSchedules->where('status', 'work')->whereNotNull('shift_id')->sortByDesc('date')->first();
            if ($latestSched) {
                $representativeShift = $latestSched->relationLoaded('shift') && $latestSched->shift
                    ? $latestSched->shift
                    : ($shiftsById ? $shiftsById->get($latestSched->shift_id) : Shift::find($latestSched->shift_id));
            }
        }

        if (!$representativeShift) {
            $representativeShift = $employee->relationLoaded('shift') ? $employee->shift : ($employee->shift_id ? $shiftsById->get($employee->shift_id) : null);
        }

        $shiftDisplayName = $representativeShift ? $representativeShift->name : 'Reguler';
        if ($representativeShift && $representativeShift->formatted_time) {
            $shiftDisplayName .= " ({$representativeShift->formatted_time})";
        }

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'photo' => $employee->photo,
            'position' => $employee->position ?: 'Karyawan',
            'branch' => $employee->area ? $employee->area->name : ($employee->branch ?: 'Pusat'),
            'area_id' => $employee->area_id,
            'shift_name' => $shiftDisplayName,
            'is_schedule_custom' => $targetInfo['is_schedule_custom'],
            'target_days' => $targetDays,
            'presents' => $presents,
            'extra_off_day_presents' => $extraOffDayPresents,
            'lates' => $totalLates,
            'late_minutes' => $totalLateMinutes,
            'alphas' => $alphas,
            'early_leaves' => $totalEarlyLeaves,
            'score' => $finalScore,
            'base_attendance_rate' => round($baseAttendanceRate, 1),
            'bonus_score' => $bonusScore,
            'avg_early_minutes' => $avgRelativeEarlyMinutes,
            'punctuality_label' => $punctualityLabel,
            'avg_clock_in_formatted' => $avgClockInFormatted,
            'avg_clock_out_formatted' => $avgClockOutFormatted,
            'reward_amount' => 0,
            'reward_title' => null,
            'rank' => '-',
            'is_branch_winner' => false,
            'is_global_winner' => false,
            'is_maintenance_winner' => false,
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
            $totalWorkDays,
            $periodDetails
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

            // 7. Hitung Hari Kerja Individu, Sakit, Izin & Alpha Secara Cerdas
            // Jika ada WorkSchedule terjadwal, gunakan data jadwal kerja
            $empWorkDays = $totalWorkDays;
            $empElapsedWorkDays = $elapsedWorkDays;
            $totalSickWithCert = 0;
            $totalSickNoCert = 0;
            $totalPermit = 0;
            $totalManualAlpha = 0;
            $hasCustomWorkSchedule = false;

            // Cek apakah karyawan sudah hadir/clock-in hari ini
            $hasClockedInToday = $empAtts->filter(function ($a) use ($todayDate) {
                $aDate = is_string($a->date) ? substr($a->date, 0, 10) : $a->date->format('Y-m-d');
                return $aDate === $todayDate && !empty($a->clock_in);
            })->isNotEmpty();

            if ($workSchedules && $workSchedules->isNotEmpty()) {
                $empSchedules = $workSchedules->where('employee_id', $emp->id);
                if ($empSchedules->isNotEmpty()) {
                    $workSchedulesCount = $empSchedules->where('status', 'work')->count();
                    if ($workSchedulesCount > 0) {
                        $hasCustomWorkSchedule = true;
                        $empWorkDays = $workSchedulesCount;
                        $todayShiftEvaluated = false;
                        $todayCountedInSchedule = false;

                        $empElapsedWorkDays = $empSchedules->where('status', 'work')
                            ->filter(function ($s) use ($todayDate, $now, $shiftsById, $settings, &$todayShiftEvaluated, &$todayCountedInSchedule) {
                                $sDate = is_string($s->date) ? substr($s->date, 0, 10) : $s->date->format('Y-m-d');
                                if ($sDate < $todayDate) {
                                    return true;
                                }
                                if ($sDate === $todayDate) {
                                    $todayShiftEvaluated = true;
                                    $schedShift = $s->shift_id ? $shiftsById->get($s->shift_id) : null;
                                    $deadline = self::resolveCheckoutDeadlineForShift($sDate, $schedShift, $settings, 'Asia/Jakarta');
                                    if ($now->greaterThan($deadline)) {
                                        $todayCountedInSchedule = true;
                                        return true;
                                    }
                                }
                                return false;
                            })->count();

                        // Jika hari ini adalah jadwal kerja dan shift belum berakhir (belum lewat checkout deadline),
                        // tapi karyawan SUDAH clock-in/hadir hari ini:
                        // hitung hari ini sebagai hari kerja berjalan agar rasio kehadiran imbang (misal 1/1 = 100%)
                        if ($todayShiftEvaluated && !$todayCountedInSchedule && $hasClockedInToday) {
                            $empElapsedWorkDays++;
                            $todayCountedInSchedule = true;
                        }
                    } else {
                        // Karyawan standar (Senin-Sabtu), kurangi jika ada jadwal OFF / Sakit / Izin
                        $offOrLeaveSchedules = $empSchedules->filter(function ($s) {
                            return $s->status !== 'work' || empty($s->shift_id);
                        });

                        $empWorkDays = max(0, $totalWorkDays - $offOrLeaveSchedules->count());

                        $elapsedOffDaysCount = $offOrLeaveSchedules->filter(function ($s) use ($todayDate) {
                            $sDate = is_string($s->date) ? substr($s->date, 0, 10) : $s->date->format('Y-m-d');
                            return $sDate <= $todayDate;
                        })->count();
                        $empElapsedWorkDays = max(0, $elapsedWorkDays - $elapsedOffDaysCount);
                    }

                    // Identifikasi Sakit & Izin dari jadwal / notes
                    foreach ($empSchedules as $s) {
                        $sDate = is_string($s->date) ? substr($s->date, 0, 10) : $s->date->format('Y-m-d');
                        if ($sDate > $todayDate) continue; // Jangan kenakan potongan untuk tanggal mendatang

                        $st = strtolower($s->status ?? '');
                        $nt = strtolower($s->notes ?? '');

                        $isSick = ($st === 'sick') || str_contains($nt, 'sakit');
                        $isLeaveOrPermit = ($st === 'leave') || ($st === 'permit') || str_contains($nt, 'izin');

                        if ($isSick) {
                            // Cek apakah tanpa surat dokter
                            if (str_contains($nt, 'tanpa surat') || str_contains($nt, 'tanpa dokter') || $st === 'sick_no_cert') {
                                $totalSickNoCert++;
                            } else {
                                $totalSickWithCert++;
                            }
                        } elseif ($isLeaveOrPermit) {
                            if (str_contains($nt, 'izin') || $st === 'permit') {
                                $totalPermit++;
                            }
                        } elseif ($st === 'alpha' || str_contains($nt, 'alpha') || str_contains($nt, 'mangkir')) {
                            $totalManualAlpha++;
                        }
                    }
                }
            }

            // Untuk karyawan non-roster / jadwal standar (Senin-Sabtu):
            // Jika hari ini belum berakhir (checkout deadline umum belum lewat), tapi karyawan sudah hadir clock-in:
            // maka hari kerja yang dijalani bertambah +1 agar seimbang dengan total kehadiran
            if (!$hasCustomWorkSchedule) {
                if ($hasClockedInToday && !($periodDetails['is_today_closed'] ?? false)) {
                    $empElapsedWorkDays++;
                }
            }

            // Alpha = hari kerja yang telah berjalan dikurangi kehadiran & dinas luar (ditambah alpha manual)
            $effectiveAttendedDays = min($empElapsedWorkDays, $totalPresent + $totalFieldDuty);
            $totalAlpha = max(0, $empElapsedWorkDays - $effectiveAttendedDays) + $totalManualAlpha;

            // 8. Rasio Kehadiran & Skor Kedisiplinan
            $attendanceRate = $empElapsedWorkDays > 0 
                ? round(($effectiveAttendedDays / $empElapsedWorkDays) * 100, 1) 
                : 0;

            $disciplineRate = $totalPresent > 0 
                ? round(($totalOnTime / $totalPresent) * 100, 1) 
                : ($totalAlpha > 0 ? 0 : 100);

            // 9. Kalkulasi Penalti / Estimasi Potongan Cerdas Sesuai Pengaturan
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

            // Potongan Sakit Tanpa Surat Dokter (Sakit dengan Surat Dokter = Rp 0 Bebas Denda)
            $sickNoCertPenalty = 0;
            if (!empty($settings['sick_no_cert_enabled']) && $totalSickNoCert > 0) {
                $sickNoCertPenalty = $totalSickNoCert * ($settings['sick_no_cert_amount'] ?? 20000);
            }

            // Potongan Izin Tidak Masuk
            $permitPenalty = 0;
            if (!empty($settings['permit_enabled']) && $totalPermit > 0) {
                $permitPenalty = $totalPermit * ($settings['permit_amount'] ?? 25000);
            }

            $totalEstimatedPenalty = $latePenalty + $missingOutPenalty + $earlyLeavePenalty + $alphaPenalty + $sickNoCertPenalty + $permitPenalty;

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
                'total_sick_with_cert' => $totalSickWithCert,
                'total_sick_no_cert' => $totalSickNoCert,
                'total_permit' => $totalPermit,
                'attendance_rate' => $attendanceRate,
                'discipline_rate' => $disciplineRate,
                'penalties' => [
                    'late' => $latePenalty,
                    'missing_out' => $missingOutPenalty,
                    'early_leave' => $earlyLeavePenalty,
                    'alpha' => $alphaPenalty,
                    'sick_no_cert' => $sickNoCertPenalty,
                    'permit' => $permitPenalty,
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
            $workSchedules,
            $settings,
            $periodDetails,
            $month,
            $monthEnd,
            &$syncedDeductionsCount,
            &$affectedEmployeesCount
        ) {
            $nowEvalSpecial = Carbon::now('Asia/Jakarta');
            $shiftsById = Shift::all()->keyBy('id');
            $parsedDate = Carbon::parse($month . '-01');

            foreach ($recap as $row) {
                $emp = Employee::find($row['employee_id']);
                if (!$emp) continue;

                $user = null;
                if (!empty($emp->email)) {
                    $user = User::where('email', $emp->email)->first();
                }
                if (!$user && !empty($emp->phone)) {
                    $user = User::where('phone', $emp->phone)->first();
                }
                if (!$user && !empty($emp->name)) {
                    $user = User::where('name', $emp->name)->first();
                }
                if (!$user) continue;

                // Jangan ubah potongan jika gaji karyawan bulan ini sudah dicairkan (status: paid)
                $isPaidPayroll = Payroll::where('user_id', $user->id)
                    ->where('month', (int) $parsedDate->month)
                    ->where('year', (int) $parsedDate->year)
                    ->where('status', 'paid')
                    ->exists();
                if ($isPaidPayroll) {
                    continue;
                }

                $hasAnyPenalty = false;
                $empAtts = $attendances->where('employee_id', $emp->id);
                $empSchedules = $workSchedules ? $workSchedules->where('employee_id', $emp->id) : collect();

                // 1. Potongan Terlambat
                if ($row['penalties']['late'] > 0) {
                    $latestLate = $empAtts->filter(function ($a) {
                        return $a->status === 'late' || (int) ($a->late_minutes ?? 0) > 0;
                    })->sortByDesc('date')->first();
                    $lateDeductDate = $latestLate ? Carbon::parse($latestLate->date)->format('Y-m-d') : $monthEnd->format('Y-m-d');

                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_LATE_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Terlambat Absensi Periode {$month} ({$row['total_late']}x telat, {$row['total_late_minutes']} menit)",
                            'amount' => $row['penalties']['late'],
                            'deduction_date' => $lateDeductDate,
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                } else {
                    Deduction::where('user_id', $user->id)
                        ->where('reference_id', "ATT_LATE_{$emp->id}_{$month}")
                        ->where('status', 'pending')
                        ->delete();
                }

                // 2. Potongan Lupa Pulang (Missing Out)
                if ($row['penalties']['missing_out'] > 0) {
                    $latestMissingOut = $empAtts->filter(function ($a) use ($nowEvalSpecial, $settings, $shiftsById) {
                        return self::isMissingClockOut($a, $nowEvalSpecial, $settings, $shiftsById, 'Asia/Jakarta');
                    })->sortByDesc('date')->first();
                    $missingOutDeductDate = $latestMissingOut ? Carbon::parse($latestMissingOut->date)->format('Y-m-d') : $monthEnd->format('Y-m-d');

                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_NO_OUT_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Lupa Absen Pulang Periode {$month} ({$row['total_missing_out']}x)",
                            'amount' => $row['penalties']['missing_out'],
                            'deduction_date' => $missingOutDeductDate,
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                } else {
                    Deduction::where('user_id', $user->id)
                        ->where('reference_id', "ATT_NO_OUT_{$emp->id}_{$month}")
                        ->where('status', 'pending')
                        ->delete();
                }

                // 3. Potongan Pulang Cepat
                if ($row['penalties']['early_leave'] > 0) {
                    $latestEarly = $empAtts->filter(function ($a) {
                        $earlyMins = (int) ($a->early_leave_minutes ?? 0);
                        if ($earlyMins > 0) return true;
                        return str_contains(strtolower($a->notes ?? ''), 'pulang lebih awal');
                    })->sortByDesc('date')->first();
                    $earlyDeductDate = $latestEarly ? Carbon::parse($latestEarly->date)->format('Y-m-d') : $monthEnd->format('Y-m-d');

                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_EARLY_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Pulang Cepat Periode {$month} ({$row['total_early_leave']}x)",
                            'amount' => $row['penalties']['early_leave'],
                            'deduction_date' => $earlyDeductDate,
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                } else {
                    Deduction::where('user_id', $user->id)
                        ->where('reference_id', "ATT_EARLY_{$emp->id}_{$month}")
                        ->where('status', 'pending')
                        ->delete();
                }

                // 4. Potongan Alpha / Mangkir
                if ($row['penalties']['alpha'] > 0) {
                    $alphaSchedule = $empSchedules->filter(function ($s) {
                        $st = strtolower($s->status ?? '');
                        $nt = strtolower($s->notes ?? '');
                        return $st === 'alpha' || str_contains($nt, 'alpha') || str_contains($nt, 'mangkir');
                    })->sortByDesc('date')->first();
                    $alphaDeductDate = $alphaSchedule 
                        ? Carbon::parse($alphaSchedule->date)->format('Y-m-d') 
                        : min(Carbon::today('Asia/Jakarta')->format('Y-m-d'), $monthEnd->format('Y-m-d'));

                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_ALPHA_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Alpha / Mangkir Kerja Periode {$month} ({$row['total_alpha']} hari)",
                            'amount' => $row['penalties']['alpha'],
                            'deduction_date' => $alphaDeductDate,
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                } else {
                    Deduction::where('user_id', $user->id)
                        ->where('reference_id', "ATT_ALPHA_{$emp->id}_{$month}")
                        ->where('status', 'pending')
                        ->delete();
                }

                // 5. Potongan Sakit Tanpa Surat Dokter
                if (($row['penalties']['sick_no_cert'] ?? 0) > 0) {
                    $sickSchedule = $empSchedules->filter(function ($s) {
                        $st = strtolower($s->status ?? '');
                        $nt = strtolower($s->notes ?? '');
                        $isSick = ($st === 'sick') || str_contains($nt, 'sakit');
                        return $isSick && (str_contains($nt, 'tanpa surat') || str_contains($nt, 'tanpa dokter') || $st === 'sick_no_cert');
                    })->sortByDesc('date')->first();
                    $sickDeductDate = $sickSchedule 
                        ? Carbon::parse($sickSchedule->date)->format('Y-m-d') 
                        : min(Carbon::today('Asia/Jakarta')->format('Y-m-d'), $monthEnd->format('Y-m-d'));

                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_SICK_NO_CERT_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Sakit Tanpa Surat Dokter Periode {$month} ({$row['total_sick_no_cert']} hari)",
                            'amount' => $row['penalties']['sick_no_cert'],
                            'deduction_date' => $sickDeductDate,
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                } else {
                    Deduction::where('user_id', $user->id)
                        ->where('reference_id', "ATT_SICK_NO_CERT_{$emp->id}_{$month}")
                        ->where('status', 'pending')
                        ->delete();
                }

                // 6. Potongan Izin Tidak Masuk
                if (($row['penalties']['permit'] ?? 0) > 0) {
                    $permitSchedule = $empSchedules->filter(function ($s) {
                        $st = strtolower($s->status ?? '');
                        $nt = strtolower($s->notes ?? '');
                        return ($st === 'leave') || ($st === 'permit') || str_contains($nt, 'izin');
                    })->sortByDesc('date')->first();
                    $permitDeductDate = $permitSchedule 
                        ? Carbon::parse($permitSchedule->date)->format('Y-m-d') 
                        : min(Carbon::today('Asia/Jakarta')->format('Y-m-d'), $monthEnd->format('Y-m-d'));

                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference_id' => "ATT_PERMIT_{$emp->id}_{$month}",
                        ],
                        [
                            'type' => 'auto',
                            'description' => "Potongan Izin Tidak Masuk Periode {$month} ({$row['total_permit']} hari)",
                            'amount' => $row['penalties']['permit'],
                            'deduction_date' => $permitDeductDate,
                            'status' => 'pending',
                        ]
                    );
                    $syncedDeductionsCount++;
                    $hasAnyPenalty = true;
                } else {
                    Deduction::where('user_id', $user->id)
                        ->where('reference_id', "ATT_PERMIT_{$emp->id}_{$month}")
                        ->where('status', 'pending')
                        ->delete();
                }

                // 7. Potongan Absensi Tim Khusus
                $empSpecialAtts = $specialAtts->where('employee_id', $emp->id);
                if ($empSpecialAtts->isNotEmpty()) {
                    $byTeam = $empSpecialAtts->groupBy('special_team_id');
                    foreach ($byTeam as $teamId => $teamAtts) {
                        $team = $teamAtts->first()->team;
                        $sLates = $teamAtts->where('status', 'late')->count();
                        $sNoOuts = $teamAtts->filter(fn($sAtt) => AttendanceCalculatorService::isSpecialMissingClockOut($sAtt, $team, $nowEvalSpecial))->count();
                        $sLateFine = $sLates * (float) ($team?->late_deduction_amount ?? 10000);
                        $sNoOutFine = $sNoOuts * (float) ($team?->no_out_deduction_amount ?? 10000);
                        $totalSpecialFine = $sLateFine + $sNoOutFine;
                        $specialRef = "SPECIAL_DEDUCT_{$teamId}_{$emp->id}_{$month}";

                        if ($totalSpecialFine > 0) {
                            $latestSpecial = $teamAtts->filter(function ($sAtt) use ($team, $nowEvalSpecial) {
                                return $sAtt->status === 'late' || AttendanceCalculatorService::isSpecialMissingClockOut($sAtt, $team, $nowEvalSpecial);
                            })->sortByDesc('date')->first();
                            $specialDeductDate = $latestSpecial 
                                ? Carbon::parse($latestSpecial->date)->format('Y-m-d') 
                                : min(Carbon::today('Asia/Jakarta')->format('Y-m-d'), $monthEnd->format('Y-m-d'));

                            $teamName = $team?->name ?? 'Tim Khusus';
                            $desc = "Potongan Absen {$teamName} Periode {$month} ({$sLates}x telat, {$sNoOuts}x tanpa absen pulang)";
                            Deduction::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'reference_id' => $specialRef,
                                ],
                                [
                                    'type' => 'auto',
                                    'description' => $desc,
                                    'amount' => $totalSpecialFine,
                                    'deduction_date' => $specialDeductDate,
                                    'status' => 'pending',
                                ]
                            );
                            $syncedDeductionsCount++;
                            $hasAnyPenalty = true;
                        } else {
                            Deduction::where('user_id', $user->id)
                                ->where('reference_id', $specialRef)
                                ->where('status', 'pending')
                                ->delete();
                        }
                    }
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

