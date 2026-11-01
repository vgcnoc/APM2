<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLeave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'type',
        'reason',
        'attachment',
        'penalty_amount',
        'recorded_by',
        'work_schedule_id',
    ];

    protected $casts = [
        'date' => 'date',
        'penalty_amount' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public static function getTypeName(string $type): string
    {
        return match ($type) {
            'sick_with_cert' => 'Sakit (Surat Dokter)',
            'sick_no_cert' => 'Sakit (Tanpa Surat)',
            'permit' => 'Izin Tidak Masuk',
            'alpha' => 'Alpha / Mangkir Kerja',
            'leave' => 'Cuti Tahunan / Khusus',
            default => ucfirst($type),
        };
    }
}
