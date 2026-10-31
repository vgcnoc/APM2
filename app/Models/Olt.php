<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Olt extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'name',
        'hostname',
        'ip_address',
        'brand',
        'model',
        'total_pon_ports',
        'pon_vlans',
        'location',
        'latitude',
        'longitude',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'total_pon_ports' => 'integer',
            'pon_vlans' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    /**
     * OLT memiliki banyak ODC (1:N)
     */
    public function pons(): HasMany
    {
        return $this->hasMany(OltPon::class);
    }

    public function odcs(): HasMany
    {
        return $this->hasMany(Odc::class);
    }

    /**
     * Area utama lama. Dipertahankan untuk kompatibilitas data jaringan lama.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Seluruh area/wilayah layanan OLT.
     */
    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'area_olt')->orderBy('areas.name');
    }

    // ── Computed ───────────────────────────────────────────────

    /**
     * Hitung total ODP di bawah OLT ini (melalui ODC)
     */
    public function getTotalOdpsAttribute(): int
    {
        return $this->odcs->sum(fn ($odc) => $odc->odps->count());
    }

    /**
     * Hitung total pelanggan aktif terhubung ke OLT ini
     */
    public function getTotalCustomersAttribute(): int
    {
        return Ont::whereHas('odp.odc', fn ($q) => $q->where('olt_id', $this->id))
            ->whereNotNull('customer_id')
            ->count();
    }
}
