<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odc extends Model
{
    use HasFactory;

    protected $fillable = [
        'olt_id',
        'pon_port',
        'area_id',
        'name',
        'type',
        'photo',
        'location',
        'latitude',
        'longitude',
        'capacity',
        'description',
        'status',
        'start_point',
        'end_point',
        'cable_pull',
        'is_split',
        'parent_odc_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'capacity' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    /**
     * ODC dimiliki oleh satu OLT (N:1)
     */
    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    /**
     * ODC memiliki banyak ODP (1:N)
     */
    public function odps(): HasMany
    {
        return $this->hasMany(Odp::class);
    }

    // ── Computed ───────────────────────────────────────────────

    public function getTotalOdpsAttribute(): int
    {
        return $this->odps()->count();
    }
}
