<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'work_schedule_id',
        'shift_id',
        'area_id',
        'attendance_type',
        'date',
        'clock_in',
        'clock_out',
        'photo_in',
        'photo_out',
        'lat_in',
        'lng_in',
        'lat_out',
        'lng_out',
        'distance_in',
        'distance_out',
        'status',
        'late_minutes',
        'notes',
        'duty_location',
        'is_verified',
        'is_synced_to_payroll',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'late_minutes' => 'integer',
        'distance_in' => 'integer',
        'distance_out' => 'integer',
    ];

    protected $appends = [
        'formatted_date',
        'is_clocked_in',
        'is_clocked_out',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->date ? Carbon::parse($this->date)->translatedFormat('l, d F Y') : '-';
    }

    public function getIsClockedInAttribute(): bool
    {
        return !empty($this->clock_in);
    }

    public function getIsClockedOutAttribute(): bool
    {
        return !empty($this->clock_out);
    }
}
