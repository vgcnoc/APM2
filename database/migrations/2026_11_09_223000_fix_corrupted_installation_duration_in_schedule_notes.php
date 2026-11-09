<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbaiki teks "(Durasi: XXXX jam Y menit)" pada catatan jadwal instalasi
     * akibat selisih lintas tanggal/tahun pada data migrasi historis.
     */
    public function up(): void
    {
        $schedules = DB::table('technician_schedules')
            ->where('notes', 'like', '%Durasi:%jam%')
            ->get(['id', 'notes']);

        foreach ($schedules as $schedule) {
            $rawNotes = (string) $schedule->notes;
            $lines = explode("\n", $rawNotes);

            $startDay = null;
            $startTotalMins = null;

            foreach ($lines as $line) {
                if (preg_match('/\[(\d{2}\/\d{2})\s+(\d{2}):(\d{2})\]/', $line, $m)) {
                    if ($startDay === null || stripos($line, 'Pemasangan dimulai') !== false) {
                        $startDay = $m[1];
                        $startTotalMins = ((int) $m[2]) * 60 + ((int) $m[3]);
                        if (stripos($line, 'Pemasangan dimulai') !== false) {
                            break;
                        }
                    }
                }
            }

            $updatedLines = [];
            $changed = false;

            foreach ($lines as $line) {
                $newLine = preg_replace_callback(
                    '/\(Durasi:\s*(\d+)\s*jam(?:\s*(\d+)\s*menit)?\)/i',
                    function ($matches) use ($line, $startDay, $startTotalMins, &$changed) {
                        $hours = (int) $matches[1];
                        $mins = isset($matches[2]) ? (int) $matches[2] : 0;

                        if ($hours < 24) {
                            return $matches[0];
                        }

                        $changed = true;

                        if (preg_match('/\[(\d{2}\/\d{2})\s+(\d{2}):(\d{2})\]/', $line, $endMatch)) {
                            if ($startDay !== null && $endMatch[1] === $startDay && $startTotalMins !== null) {
                                $endTotalMins = ((int) $endMatch[2]) * 60 + ((int) $endMatch[3]);
                                $diff = $endTotalMins - $startTotalMins;
                                if ($diff >= 0 && $diff < 1440) {
                                    $dHours = intdiv($diff, 60);
                                    $dMins = $diff % 60;
                                    if ($dHours > 0) {
                                        return '(Durasi: ' . $dHours . ' jam' . ($dMins > 0 ? ' ' . $dMins . ' menit' : '') . ')';
                                    }
                                    return '(Durasi: ' . max(1, $dMins) . ' menit)';
                                }
                            }
                        }

                        $remHours = $hours % 24;
                        if ($remHours > 0 && $remHours <= 5) {
                            return '(Durasi: ' . $remHours . ' jam' . ($mins > 0 ? ' ' . $mins . ' menit' : '') . ')';
                        }

                        return '(Durasi: ' . ($mins > 0 ? $mins : 10) . ' menit)';
                    },
                    $line
                );

                $updatedLines[] = $newLine;
            }

            if ($changed) {
                DB::table('technician_schedules')
                    ->where('id', $schedule->id)
                    ->update(['notes' => implode("\n", $updatedLines)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data fix, no rollback needed
    }
};

