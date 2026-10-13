<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_night_shift',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_night_shift' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'formatted_time',
        'duration_text',
    ];

    /**
     * Karyawan yang mendapatkan shift ini
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Jadwal kerja yang menggunakan shift ini
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(WorkSchedule::class);
    }

    /**
     * Format jam kerja: "08:00 - 17:00"
     */
    public function getFormattedTimeAttribute(): string
    {
        $start = substr($this->start_time ?? '', 0, 5);
        $end = substr($this->end_time ?? '', 0, 5);
        return "{$start} - {$end}";
    }

    /**
     * Hitung durasi jam kerja secara otomatis
     */
    public function getDurationTextAttribute(): string
    {
        if (!$this->start_time || !$this->end_time) {
            return '-';
        }

        try {
            $startParts = explode(':', $this->start_time);
            $endParts = explode(':', $this->end_time);

            $startMinutes = ((int) $startParts[0] * 60) + (int) ($startParts[1] ?? 0);
            $endMinutes = ((int) $endParts[0] * 60) + (int) ($endParts[1] ?? 0);

            if ($endMinutes < $startMinutes) {
                // Cross midnight (e.g. 22:00 to 06:00)
                $diff = (1440 - $startMinutes) + $endMinutes;
            } else {
                $diff = $endMinutes - $startMinutes;
            }

            $hours = floor($diff / 60);
            $minutes = $diff % 60;

            if ($minutes > 0) {
                return "{$hours} Jam {$minutes} Menit";
            }
            return "{$hours} Jam";
        } catch (\Throwable $e) {
            return '-';
        }
    }
}
