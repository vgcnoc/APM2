<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\WorkSchedule;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $date = '2026-11-06';
        $employeeIds = [35, 48]; // Rifkiansyah (ID 35) dan Riski Maulana (ID 48)

        // Hapus presensi kompensasi otomatis pada tanggal 06 Nov 2026
        // untuk karyawan yang jadwal resminya sedang OFF / Libur
        Attendance::whereIn('employee_id', $employeeIds)
            ->whereDate('date', $date)
            ->where('notes', 'like', '%Kompensasi gangguan internet%')
            ->delete();

        // Pastikan work_schedules mereka tetap berstatus 'off' sesuai pola roster mereka
        foreach ($employeeIds as $empId) {
            WorkSchedule::updateOrCreate(
                ['employee_id' => $empId, 'date' => $date],
                ['status' => 'off', 'shift_id' => null, 'notes' => 'Hari Libur Rutin']
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu reverse
    }
};
