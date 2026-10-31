<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\AttendanceCalculatorService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoEvaluateAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-evaluate {--month= : Bulan yang dievaluasi (format: YYYY-MM)} {--sync-payroll : Otomatis sinkronkan potongan ke penggajian}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluasi cerdas presensi harian, auto-lock checkout, dan kalkulasi rekapitulasi awal s/d akhir bulan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today('Asia/Jakarta');
        $month = $this->option('month') ?: $today->format('Y-m');
        $periodDetails = AttendanceCalculatorService::getMonthPeriodDetails($month);

        $this->info("Menjalankan Evaluasi Presensi Cerdas Periode: {$periodDetails['period_label']} ({$month})...");

        // 1. Auto-evaluate absensi hari ini yang belum clock-out setelah batas waktu
        $cutoffTime = Setting::get('attendance_checkout_deadline_time', '18:30');
        $now = Carbon::now('Asia/Jakarta');
        $cutoffCarbon = Carbon::parse($today->format('Y-m-d') . ' ' . $cutoffTime, 'Asia/Jakarta');

        $missingOutCount = 0;
        if ($now->greaterThanOrEqualTo($cutoffCarbon)) {
            $unclosedAttendances = Attendance::where('date', $today->format('Y-m-d'))
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->get();

            foreach ($unclosedAttendances as $att) {
                // Beri catatan sistem jika belum ada
                if (empty($att->notes)) {
                    $att->notes = "Auto-Closed oleh Sistem (Melewati batas checkout {$cutoffTime} WIB)";
                    $att->save();
                }
                $missingOutCount++;
            }
            $this->info("Evaluasi Clock-Out Hari Ini: {$missingOutCount} karyawan tidak absen pulang.");
        }

        // 2. Kalkulasi Rekapitulasi Periode Awal s/d Akhir Bulan
        $employees = Employee::where(function ($q) {
            $q->where('status', 'active')->orWhereNull('status');
        })->get();

        $attendances = Attendance::whereBetween('date', [$periodDetails['start_date'], $periodDetails['end_date']])->get();
        $recap = AttendanceCalculatorService::calculateMonthlyRecap($employees, $attendances, $periodDetails);

        $totalPresent = $recap->sum('total_present');
        $totalLate = $recap->sum('total_late');
        $totalAlpha = $recap->sum('total_alpha');
        $totalPenalty = $recap->sum('estimated_penalty');

        $this->table(
            ['Metrik Presensi', 'Nilai'],
            [
                ['Periode Perhitungan', $periodDetails['period_label']],
                ['Total Hari Kalender', $periodDetails['total_calendar_days'] . ' Hari'],
                ['Total Hari Kerja Sebulan', $periodDetails['total_work_days'] . ' Hari'],
                ['Hari Kerja Berjalan', $periodDetails['elapsed_work_days'] . ' Hari'],
                ['Total Karyawan Aktif', $employees->count() . ' Orang'],
                ['Total Hadir', $totalPresent . ' Kali'],
                ['Total Keterlambatan', $totalLate . ' Kali'],
                ['Total Alpha (Otomatis)', $totalAlpha . ' Hari'],
                ['Estimasi Total Penalti / Potongan', 'Rp ' . number_format($totalPenalty, 0, ',', '.')],
            ]
        );

        // 3. Opsional: Sinkronkan ke Payroll
        if ($this->option('sync-payroll')) {
            $this->info("Menyinkronkan data potongan ke modul Penggajian (Payroll)...");
            $syncRes = AttendanceCalculatorService::syncMonthlyToPayroll($month);
            $this->info("Sinkronisasi Selesai: {$syncRes['synced_deductions_count']} potongan untuk {$syncRes['affected_employees_count']} karyawan.");
        }

        Log::info("AutoEvaluateAttendance selesai untuk periode {$month}.", [
            'total_present' => $totalPresent,
            'total_late' => $totalLate,
            'total_alpha' => $totalAlpha,
            'total_penalty' => $totalPenalty,
        ]);

        $this->info("Evaluasi presensi cerdas berhasil diselesaikan.");
        return Command::SUCCESS;
    }
}

