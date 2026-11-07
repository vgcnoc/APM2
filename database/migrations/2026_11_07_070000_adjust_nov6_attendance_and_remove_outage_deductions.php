<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\SpecialAttendance;
use App\Models\WorkSchedule;
use App\Models\Deduction;
use App\Models\Shift;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $date = '2026-11-06';
        $notesText = 'Presensi disesuaikan (Kompensasi gangguan internet VPS 06 Nov 2026)';

        // 1. Buatkan presensi bagi karyawan aktif yang belum ada record presensi pada 6 November 2026
        // Rifkiansyah (ID 35) - Shift 1-Kolektor (ID 10: 08:35 - 17:10)
        WorkSchedule::updateOrCreate(
            ['employee_id' => 35, 'date' => $date],
            ['status' => 'work', 'shift_id' => 10]
        );
        Attendance::updateOrCreate(
            ['employee_id' => 35, 'date' => $date],
            [
                'shift_id' => 10,
                'area_id' => 1,
                'attendance_type' => 'regular',
                'clock_in' => '08:25:00',
                'clock_out' => '17:15:00',
                'status' => 'present',
                'late_minutes' => 0,
                'is_verified' => 1,
                'notes' => $notesText,
            ]
        );

        // Riski Maulana (ID 48) - Shift 1-Teknisi (ID 13: 08:35 - 17:10)
        WorkSchedule::updateOrCreate(
            ['employee_id' => 48, 'date' => $date],
            ['status' => 'work', 'shift_id' => 13]
        );
        Attendance::updateOrCreate(
            ['employee_id' => 48, 'date' => $date],
            [
                'shift_id' => 13,
                'area_id' => 1,
                'attendance_type' => 'regular',
                'clock_in' => '08:24:00',
                'clock_out' => '17:16:00',
                'status' => 'present',
                'late_minutes' => 0,
                'is_verified' => 1,
                'notes' => $notesText,
            ]
        );

        // Dea Aulia (ID 53) - Shift 1-CS (ID 3: 07:00 - 15:00)
        WorkSchedule::updateOrCreate(
            ['employee_id' => 53, 'date' => $date],
            ['status' => 'work', 'shift_id' => 3]
        );
        Attendance::updateOrCreate(
            ['employee_id' => 53, 'date' => $date],
            [
                'shift_id' => 3,
                'area_id' => 1,
                'attendance_type' => 'regular',
                'clock_in' => '06:50:00',
                'clock_out' => '15:08:00',
                'status' => 'present',
                'late_minutes' => 0,
                'is_verified' => 1,
                'notes' => $notesText,
            ]
        );

        // 2. Lengkapi jam pulang (clock_out) & normalisasi keterlambatan akibat VPS down pada presensi reguler 06 Nov 2026
        $regularCheckoutUpdates = [
            54 => ['out' => '15:05:00'], // Doni (Shift 1-CS: 07:00 - 15:00)
            49 => ['out' => '17:15:00'], // Solehudin (Shift 1-Teknisi: 08:35 - 17:10)
            39 => ['out' => '17:12:00'], // Ariyani Setyawati (Shift 1/Admin: 09:00 - 17:10)
            33 => ['out' => '17:14:00'], // Ujang Mulyana (Shift 1-Kolektor: 08:35 - 17:10)
            44 => ['out' => '17:15:00'], // Ruli Septiadi (Shift 1-Teknisi: 08:35 - 17:10)
            58 => ['out' => '17:16:00'], // Andriansyah (Shift 1-Teknisi: 08:35 - 17:10)
            55 => ['out' => '17:15:00'], // Agung Rusmawan (Shift 1-Teknisi: 08:35 - 17:10)
            36 => ['out' => '17:18:00'], // Damar (Shift 1-Teknisi: 08:35 - 17:10)
            51 => ['out' => '17:15:00'], // Rafli Agustian (Shift 1-Teknisi: 08:35 - 17:10)
            56 => ['out' => '17:12:00'], // Yuda Aditia Saputra (Shift 1-Teknisi: 08:35 - 17:10)
            43 => ['out' => '17:15:00'], // Bastian Surya Wirasanjaya (Shift 1-Teknisi: 08:35 - 17:10)
            50 => ['in' => '08:30:00', 'out' => '17:15:00', 'late' => 0, 'status' => 'present'], // Saepul
            41 => ['out' => '08:02:00'], // Sandi Permana Majid (Shift Malam: 23:00 - 08:00)
            42 => ['in' => '14:52:00', 'out' => '23:05:00', 'late' => 0, 'status' => 'present'], // Hikmah Aprilianti (Shift 2-CS: 15:00 - 23:00)
        ];

        foreach ($regularCheckoutUpdates as $empId => $vals) {
            $att = Attendance::where('employee_id', $empId)->where('date', $date)->first();
            if ($att) {
                $payload = [];
                if (isset($vals['out']) && (empty($att->clock_out) || $att->clock_out < '09:00:00')) {
                    $payload['clock_out'] = $vals['out'];
                }
                if (isset($vals['in'])) {
                    $payload['clock_in'] = $vals['in'];
                }
                if (isset($vals['late'])) {
                    $payload['late_minutes'] = $vals['late'];
                }
                if (isset($vals['status'])) {
                    $payload['status'] = $vals['status'];
                }
                $payload['notes'] = $notesText;
                $att->update($payload);
            }
        }

        // 3. Lengkapi jam pulang (clock_out) & normalisasi keterlambatan pada Tim Khusus (SpecialAttendance) 06 Nov 2026
        $specialCheckoutUpdates = [
            40 => '18:05:00', // Yosep Ihsan Nurpalah
            61 => '18:02:00', // DIKI MUHAMAD AKBAR
            52 => '18:10:00', // Imalulhakim
            38 => '18:05:00', // Niken Kusumawati
            46 => '18:08:00', // Dzikri H Fatta
            45 => '18:04:00', // Paisal Muharam
            60 => '18:02:00', // YAHYA MAULANA
            37 => '18:05:00', // Supriatna
            47 => '18:06:00', // Sirojudin
        ];

        foreach ($specialCheckoutUpdates as $empId => $clockOutTime) {
            $sp = SpecialAttendance::where('employee_id', $empId)->where('date', $date)->first();
            if ($sp) {
                $spPayload = ['clock_out' => $clockOutTime];
                if ($empId === 47) { // Sirojudin
                    $spPayload['clock_in'] = '08:25:00';
                    $spPayload['status'] = 'present';
                    $spPayload['late_minutes'] = 0;
                }
                $spPayload['notes'] = $notesText;
                $sp->update($spPayload);
            }
        }

        // 4. Hapus seluruh potongan (Deductions) yang ditimbulkan oleh putusnya koneksi internet VPS pada 06 Nov 2026
        $refPrefixes = [
            'ATT_NO_OUT_',
            'SPECIAL_DEDUCT_',
            'ATT_LATE_42_2026-11',
            'ATT_LATE_50_2026-11',
        ];

        // Hapus potongan spesifik tanggal 06 Nov 2026 atau yang mereferensikan insiden lupa pulang / telat hari tersebut
        Deduction::where('deduction_date', $date)
            ->where(function ($q) {
                $q->where('description', 'like', '%tanpa absen pulang%')
                  ->orWhere('description', 'like', '%Lupa Absen Pulang%')
                  ->orWhere('reference_id', 'like', 'ATT_NO_OUT_%')
                  ->orWhere('reference_id', 'like', 'SPECIAL_DEDUCT_%')
                  ->orWhere('reference_id', 'ATT_LATE_42_2026-11')
                  ->orWhere('reference_id', 'ATT_LATE_50_2026-11');
            })
            ->where('status', 'pending')
            ->delete();

        // 5. Sinkronkan ulang potongan agar bersih dan akurat
        try {
            \App\Services\AttendanceCalculatorService::syncMonthlyToPayroll('2026-11');
        } catch (\Throwable $e) {
            // Abaikan jika dipanggil saat migrasi CLI
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for compensation adjustment
    }
};
