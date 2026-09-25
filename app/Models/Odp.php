<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odp extends Model
{
    use HasFactory;

    protected $fillable = [
        'odc_id',
        'name',
        'latitude',
        'longitude',
        'total_ports',
        'used_ports',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'total_ports' => 'integer',
            'used_ports' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    /**
     * ODP dimiliki oleh satu ODC (N:1)
     */
    public function odc(): BelongsTo
    {
        return $this->belongsTo(Odc::class);
    }

    /**
     * ODP memiliki banyak ONT (1:N)
     */
    public function onts(): HasMany
    {
        return $this->hasMany(Ont::class);
    }

    /**
     * ODP memiliki banyak Survey (1:N)
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    // ── Computed ───────────────────────────────────────────────

    /**
     * Port yang tersedia = total - terpakai
     */
    public function getAvailablePortsAttribute(): int
    {
        return max(0, $this->total_ports - $this->used_ports);
    }

    /**
     * Apakah ODP ini masih memiliki port kosong?
     */
    public function getHasAvailablePortAttribute(): bool
    {
        return $this->available_ports > 0;
    }

    /**
     * Persentase pemakaian port
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->total_ports === 0) return 0;
        return round(($this->used_ports / $this->total_ports) * 100, 1);
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeHasAvailablePort($query)
    {
        return $query->whereRaw('used_ports < total_ports');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
