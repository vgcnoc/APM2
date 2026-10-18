<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FieldDuty;
use App\Models\Incentive;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FieldDutyController extends Controller
{
    /**
     * Halaman Utama Absen Tugas Luar
     */
    public function index(Request $request)
    {
        $query = FieldDuty::with('employee');

        // Filter Tab: 'active' (Tugas Aktif) vs 'history' (Semua Riwayat)
        $tab = $request->input('tab', 'active');
        if ($tab === 'active') {
            $query->where('status', 'active');
        }

        // Filter Tipe: 'all', 'reguler', 'non_reguler'
        $type = $request->input('type', 'all');
        if ($type === 'reguler') {
            $query->where('duty_type', 'reguler');
        } elseif ($type === 'non_reguler') {
            $query->where('duty_type', 'non_reguler');
        }

        // Pencarian Karyawan atau Lokasi
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('destination_location', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('name', 'like', "%{$search}%")
                         ->orWhere('position', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // Data Tugas & Statistik
        $duties = $query->orderBy('date', 'desc')
            ->orderBy('scheduled_start_time', 'desc')
            ->paginate(20)
            ->withQueryString();

        $activeCount = FieldDuty::where('status', 'active')->count();
        $historyCount = FieldDuty::count();

        // Konfigurasi Pengaturan Sistem
        $settings = [
            'duty_replace_regular_in' => Setting::get('duty_replace_regular_in', '1') === '1',
            'duty_replace_regular_out' => Setting::get('duty_replace_regular_out', '1') === '1',
            'duty_allowance_enabled' => Setting::get('duty_allowance_enabled', '0') === '1',
            'duty_allowance_amount' => (int) Setting::get('duty_allowance_amount', 25000),
            'duty_auto_approval' => Setting::get('duty_auto_approval', '1') === '1',
        ];

        // Daftar Seluruh Karyawan untuk Modal Buat Tugas
        $allEmployees = Employee::orderBy('name')->get(['id', 'name', 'position', 'branch', 'email']);

        return Inertia::render('Attendances/FieldDuty', [
            'duties' => $duties,
            'activeCount' => $activeCount,
            'historyCount' => $historyCount,
            'settings' => $settings,
            'allEmployees' => $allEmployees,
            'filters' => $request->only(['tab', 'type', 'search', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Buat Penugasan Luar Baru (+ Buat Tugas)
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'duty_type' => 'required|in:reguler,non_reguler',
            'destination_location' => 'required|string|max:255',
            'purpose' => 'nullable|string|max:500',
            'attendance_mode' => 'required|in:replace_both,replace_in,replace_out,none',
            'scheduled_start_time' => 'nullable',
            'scheduled_end_time' => 'nullable',
            'duty_allowance_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $durationMinutes = 60;
        if ($request->scheduled_start_time && $request->scheduled_end_time) {
            $start = Carbon::parse($request->date . ' ' . $request->scheduled_start_time);
            $end = Carbon::parse($request->date . ' ' . $request->scheduled_end_time);
            $durationMinutes = max(15, $start->diffInMinutes($end));
        }

        $allowance = $request->duty_allowance_amount;
        if ($allowance === null && Setting::get('duty_allowance_enabled', '0') === '1') {
            $allowance = (float) Setting::get('duty_allowance_amount', 25000);
        }

        $duty = FieldDuty::create([
            'employee_id' => $request->employee_id,
            'created_by_user_id' => auth()->id(),
            'date' => $request->date,
            'duty_type' => $request->duty_type,
            'destination_location' => $request->destination_location,
            'purpose' => $request->purpose ?: 'Tugas Luar / Lapangan',
            'attendance_mode' => $request->attendance_mode,
            'scheduled_start_time' => $request->scheduled_start_time ? ($request->scheduled_start_time . ':00') : null,
            'scheduled_end_time' => $request->scheduled_end_time ? ($request->scheduled_end_time . ':00') : null,
            'scheduled_duration_minutes' => $durationMinutes,
            'status' => 'active',
            'approval_status' => Setting::get('duty_auto_approval', '1') === '1' ? 'auto' : 'pending',
            'duty_allowance_amount' => $allowance ?? 0,
            'notes' => $request->notes,
        ]);

        return back()->with('success', "Penugasan luar untuk karyawan berhasil dibuat ke lokasi '{$duty->destination_location}'.");
    }

    /**
     * Check-In / Absen Berangkat Tugas Luar (Foto Selfie & Koordinat GPS)
     */
    public function checkIn(Request $request, FieldDuty $fieldDuty)
    {
        $request->validate([
            'photo' => 'required|string', // base64
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $dateStr = Carbon::parse($fieldDuty->date)->format('Y-m-d');

        // Simpan Foto Selfie
        $photoPath = $this->saveBase64Photo(
            $request->photo,
            'field_duties',
            "duty_in_{$fieldDuty->id}_{$dateStr}"
        );

        $fieldDuty->update([
            'check_in_time' => $currentTime,
            'check_in_photo' => $photoPath,
            'lat_in' => $request->latitude,
            'lng_in' => $request->longitude,
            'location_name_in' => $request->location_name ?: $fieldDuty->destination_location,
            'notes' => $request->notes ?: $fieldDuty->notes,
        ]);

        // SINKRONISASI CERDAS KE PRESENSI REGULER (PENGGANTI ABSEN MASUK)
        $shouldReplaceIn = in_array($fieldDuty->attendance_mode, ['replace_both', 'replace_in']) 
            && Setting::get('duty_replace_regular_in', '1') === '1';

        if ($shouldReplaceIn) {
            $schedule = WorkSchedule::where('employee_id', $fieldDuty->employee_id)
                ->where('date', $dateStr)
                ->first();
            $shiftId = $schedule?->shift_id ?: ($fieldDuty->employee?->shift_id);

            $regularAtt = Attendance::firstOrNew([
                'employee_id' => $fieldDuty->employee_id,
                'date' => $dateStr,
            ]);

            // Jika belum ada clock in reguler, ganti dengan tugas luar
            if (empty($regularAtt->clock_in)) {
                $regularAtt->clock_in = $currentTime;
                $regularAtt->photo_in = $photoPath;
                $regularAtt->lat_in = $request->latitude;
                $regularAtt->lng_in = $request->longitude;
                $regularAtt->status = 'present'; // Langsung Tepat Waktu (Dispensasi Tugas Luar)
                $regularAtt->late_minutes = 0;
                $regularAtt->attendance_type = 'dinas_luar';
                $regularAtt->duty_location = $fieldDuty->destination_location;
                if ($schedule && empty($regularAtt->work_schedule_id)) {
                    $regularAtt->work_schedule_id = $schedule->id;
                }
                if ($shiftId && empty($regularAtt->shift_id)) {
                    $regularAtt->shift_id = $shiftId;
                }
                $regularAtt->notes = "Absen Masuk otomatis via Tugas Luar: {$fieldDuty->destination_location} ({$fieldDuty->purpose})";
                $regularAtt->save();

                $fieldDuty->update(['is_regular_attendance_synced' => true]);
            }
        }

        return back()->with('success', "Absen Masuk Tugas Luar berhasil dicatat jam {$currentTime}." . ($shouldReplaceIn ? " Tersinkronisasi otomatis sebagai pengganti absen masuk reguler." : ""));
    }

    /**
     * Check-Out / Absen Kembali Tugas Luar (Foto Selfie Selesai Tugas)
     */
    public function checkOut(Request $request, FieldDuty $fieldDuty)
    {
        $request->validate([
            'photo' => 'required|string', // base64
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $dateStr = Carbon::parse($fieldDuty->date)->format('Y-m-d');

        // Simpan Foto Selfie Selesai Tugas
        $photoPath = $this->saveBase64Photo(
            $request->photo,
            'field_duties',
            "duty_out_{$fieldDuty->id}_{$dateStr}"
        );

        $fieldDuty->update([
            'check_out_time' => $currentTime,
            'check_out_photo' => $photoPath,
            'lat_out' => $request->latitude,
            'lng_out' => $request->longitude,
            'location_name_out' => $request->location_name ?: $fieldDuty->destination_location,
            'status' => 'completed',
        ]);

        // SINKRONISASI CERDAS KE PRESENSI REGULER (PENGGANTI ABSEN PULANG)
        $shouldReplaceOut = in_array($fieldDuty->attendance_mode, ['replace_both', 'replace_out']) 
            && Setting::get('duty_replace_regular_out', '1') === '1';

        if ($shouldReplaceOut) {
            $schedule = WorkSchedule::where('employee_id', $fieldDuty->employee_id)
                ->where('date', $dateStr)
                ->first();
            $shiftId = $schedule?->shift_id ?: ($fieldDuty->employee?->shift_id);

            $regularAtt = Attendance::firstOrNew([
                'employee_id' => $fieldDuty->employee_id,
                'date' => $dateStr,
            ]);

            $regularAtt->clock_out = $currentTime;
            $regularAtt->photo_out = $photoPath;
            $regularAtt->lat_out = $request->latitude;
            $regularAtt->lng_out = $request->longitude;
            $regularAtt->attendance_type = 'dinas_luar';
            $regularAtt->duty_location = $fieldDuty->destination_location;
            if ($schedule && empty($regularAtt->work_schedule_id)) {
                $regularAtt->work_schedule_id = $schedule->id;
            }
            if ($shiftId && empty($regularAtt->shift_id)) {
                $regularAtt->shift_id = $shiftId;
            }
            if (empty($regularAtt->clock_in) && !empty($fieldDuty->check_in_time)) {
                $regularAtt->clock_in = $fieldDuty->check_in_time;
                $regularAtt->photo_in = $fieldDuty->check_in_photo;
                $regularAtt->status = 'present';
                $regularAtt->late_minutes = 0;
            }
            $regularAtt->notes = $regularAtt->notes 
                ? ($regularAtt->notes . " | Absen Pulang otomatis via Tugas Luar: {$fieldDuty->destination_location}") 
                : "Absen Pulang otomatis via Tugas Luar: {$fieldDuty->destination_location}";
            $regularAtt->save();

            $fieldDuty->update(['is_regular_attendance_synced' => true]);
        }

        // SINKRONISASI UANG TUGAS KE PAYROLL JIKA AKTIF
        if (Setting::get('duty_allowance_enabled', '0') === '1' && $fieldDuty->duty_allowance_amount > 0 && !$fieldDuty->is_allowance_synced) {
            $emp = $fieldDuty->employee;
            if ($emp && $emp->email) {
                $user = User::where('email', $emp->email)->first();
                if ($user) {
                    Incentive::create([
                        'user_id' => $user->id,
                        'type' => 'auto',
                        'amount' => $fieldDuty->duty_allowance_amount,
                        'description' => "Uang Tugas Luar ({$fieldDuty->destination_location} - {$fieldDuty->purpose})",
                        'incentive_date' => $dateStr,
                        'status' => 'pending',
                    ]);
                    $fieldDuty->update(['is_allowance_synced' => true]);
                }
            }
        }

        return back()->with('success', "Absen Kembali / Selesai Tugas Luar berhasil dicatat jam {$currentTime}." . ($shouldReplaceOut ? " Tersinkronisasi otomatis sebagai pengganti absen pulang reguler." : ""));
    }

    /**
     * Perbarui Pengaturan Absen Tugas Luar (Modal Pengaturan)
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'duty_replace_regular_in' => 'nullable|boolean',
            'duty_replace_regular_out' => 'nullable|boolean',
            'duty_allowance_enabled' => 'nullable|boolean',
            'duty_allowance_amount' => 'required|numeric|min:0',
            'duty_auto_approval' => 'nullable|boolean',
        ]);

        Setting::set('duty_replace_regular_in', $request->boolean('duty_replace_regular_in') ? '1' : '0');
        Setting::set('duty_replace_regular_out', $request->boolean('duty_replace_regular_out') ? '1' : '0');
        Setting::set('duty_allowance_enabled', $request->boolean('duty_allowance_enabled') ? '1' : '0');
        Setting::set('duty_allowance_amount', $request->duty_allowance_amount);
        Setting::set('duty_auto_approval', $request->boolean('duty_auto_approval') ? '1' : '0');

        return back()->with('success', 'Pengaturan Absen Tugas Luar berhasil disimpan.');
    }

    /**
     * Hapus Penugasan Luar
     */
    public function destroy(FieldDuty $fieldDuty)
    {
        $fieldDuty->delete();
        return back()->with('success', 'Data tugas luar berhasil dihapus.');
    }

    /**
     * Hapus Masal Penugasan Luar
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:field_duties,id',
        ]);

        FieldDuty::whereIn('id', $request->ids)->delete();
        return back()->with('success', count($request->ids) . ' tugas luar berhasil dihapus.');
    }

    /**
     * Import Penugasan Luar dari CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Gagal membuka file CSV.');
        }

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong atau tidak valid.');
        }

        // Normalisasi header
        $headerMap = [];
        foreach ($header as $idx => $col) {
            $cleanCol = strtolower(trim($col, "\xEF\xBB\xBF \t\n\r\0\x0B"));
            $headerMap[$cleanCol] = $idx;
        }

        $imported = 0;
        $employees = Employee::all();

        while (($row = fgetcsv($handle, 2000, ',')) !== false) {
            if (empty(array_filter($row))) continue;

            $empQuery = $row[$headerMap['karyawan'] ?? $headerMap['employee'] ?? $headerMap['nama'] ?? 0] ?? null;
            if (!$empQuery) continue;

            $emp = $employees->first(function ($e) use ($empQuery) {
                return strcasecmp($e->name, trim($empQuery)) === 0 
                    || strcasecmp($e->email, trim($empQuery)) === 0
                    || (string)$e->id === trim($empQuery);
            });

            if (!$emp) continue;

            $date = $row[$headerMap['tanggal'] ?? $headerMap['date'] ?? 1] ?? date('Y-m-d');
            $dutyType = strtolower($row[$headerMap['tipe'] ?? $headerMap['type'] ?? 2] ?? 'reguler');
            if (!in_array($dutyType, ['reguler', 'non_reguler'])) {
                $dutyType = 'reguler';
            }

            $loc = $row[$headerMap['lokasi tujuan'] ?? $headerMap['lokasi'] ?? $headerMap['destination'] ?? 3] ?? 'Lokasi Tugas';
            $purpose = $row[$headerMap['maksud'] ?? $headerMap['keterangan'] ?? $headerMap['purpose'] ?? 4] ?? 'Tugas Luar';
            $mode = strtolower($row[$headerMap['mode absen'] ?? $headerMap['mode'] ?? 5] ?? 'replace_both');
            if (!in_array($mode, ['replace_both', 'replace_in', 'replace_out', 'none'])) {
                $mode = 'replace_both';
            }

            $startTime = $row[$headerMap['jam mulai'] ?? $headerMap['jadwal mulai'] ?? 6] ?? null;
            $endTime = $row[$headerMap['jam selesai'] ?? $headerMap['jadwal selesai'] ?? 7] ?? null;
            $notes = $row[$headerMap['catatan'] ?? $headerMap['notes'] ?? 8] ?? null;

            FieldDuty::create([
                'employee_id' => $emp->id,
                'created_by_user_id' => auth()->id(),
                'date' => Carbon::parse($date)->format('Y-m-d'),
                'duty_type' => $dutyType,
                'destination_location' => trim($loc),
                'purpose' => trim($purpose),
                'attendance_mode' => $mode,
                'scheduled_start_time' => $startTime ? (strlen($startTime) === 5 ? $startTime . ':00' : $startTime) : null,
                'scheduled_end_time' => $endTime ? (strlen($endTime) === 5 ? $endTime . ':00' : $endTime) : null,
                'scheduled_duration_minutes' => 60,
                'status' => 'active',
                'approval_status' => Setting::get('duty_auto_approval', '1') === '1' ? 'auto' : 'pending',
                'duty_allowance_amount' => Setting::get('duty_allowance_enabled', '0') === '1' ? (float) Setting::get('duty_allowance_amount', 25000) : 0,
                'notes' => $notes ? trim($notes) : null,
            ]);

            $imported++;
        }

        fclose($handle);

        return back()->with('success', "Berhasil mengimpor {$imported} data penugasan luar.");
    }

    /**
     * Helper simpan string base64 gambar selfie ke storage disk
     */
    private function saveBase64Photo(string $base64Data, string $folder, string $filename): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]);
        } else {
            $data = $base64Data;
            $type = 'jpg';
        }

        $decoded = base64_decode($data);
        if ($decoded === false) {
            throw new \Exception('Gagal memproses gambar selfie tugas luar.');
        }

        $filePath = "{$folder}/{$filename}_" . time() . ".{$type}";
        Storage::disk('public')->put($filePath, $decoded);

        return $filePath;
    }
}
