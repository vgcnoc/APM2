<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Employee extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'join_date' => 'date',
        'resign_date' => 'date',
        'base_salary' => 'integer',
    ];

    protected $appends = [
        'lama_bekerja',
        'tenure_level',
        'tenure_badge_label',
        'tenure_months',
        'is_resigned',
        'is_active',
    ];

    public function getIsResignedAttribute(): bool
    {
        return $this->status === 'resigned';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' || empty($this->status);
    }

    /**
     * Hitung teks deskriptif lama bekerja (contoh: "2 Tahun 4 Bulan" atau "5 Bulan 12 Hari")
     * Jika karyawan sudah resign, hitung hingga tanggal resign (bukan tanggal hari ini).
     */
    public function getLamaBekerjaAttribute(): string
    {
        if (!$this->join_date) {
            return '-';
        }

        $join = Carbon::parse($this->join_date)->startOfDay();
        $endDate = ($this->status === 'resigned' && $this->resign_date)
            ? Carbon::parse($this->resign_date)->startOfDay()
            : Carbon::now()->startOfDay();

        if ($join->isAfter($endDate)) {
            return 'Belum Mulai';
        }

        $diff = $join->diff($endDate);
        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y . ' Tahun';
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' Bulan';
        }
        if (empty($parts) || ($diff->y === 0 && $diff->m === 0)) {
            $parts[] = max(1, $diff->d) . ' Hari';
        }

        return implode(' ', $parts);
    }

    /**
     * Kategori level masa kerja:
     * - senior: > 3 Tahun
     * - middle: 1 - 3 Tahun
     * - junior: 3 Bulan - 1 Tahun
     * - new: < 3 Bulan
     * - unset: Belum diset tanggal gabung
     */
    public function getTenureLevelAttribute(): string
    {
        if (!$this->join_date) {
            return 'unset';
        }

        $join = Carbon::parse($this->join_date)->startOfDay();
        $now = Carbon::now()->startOfDay();

        if ($join->isAfter($now)) {
            return 'future';
        }

        $months = $join->diffInMonths($now);

        if ($months >= 36) {
            return 'senior';
        } elseif ($months >= 12) {
            return 'middle';
        } elseif ($months >= 3) {
            return 'junior';
        } else {
            return 'new';
        }
    }

    /**
     * Label teks badge masa kerja
     */
    public function getTenureBadgeLabelAttribute(): string
    {
        return match ($this->tenure_level) {
            'senior' => 'Senior',
            'middle' => 'Menengah',
            'junior' => 'Junior',
            'new' => 'Karyawan Baru',
            'future' => 'Akan Mulai',
            default => 'Belum Diset',
        };
    }

    /**
     * Hitung total bulan masa kerja untuk sorting/filter
     */
    public function getTenureMonthsAttribute(): int
    {
        if (!$this->join_date) {
            return 0;
        }

        $join = Carbon::parse($this->join_date)->startOfDay();
        $now = Carbon::now()->startOfDay();

        return $join->isFuture() ? 0 : (int) $join->diffInMonths($now);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function schedules()
    {
        return $this->hasMany(WorkSchedule::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}

