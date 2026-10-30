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
        'area_id',
        'name',
        'type',
        'address',
        'latitude',
        'longitude',
        'start_point',
        'end_point',
        'cable_pull',
        'photo',
        'is_split',
        'parent_odp_id',
        'total_ports',
        'used_ports',
        'core_capacity',
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
            'core_capacity' => 'integer',
        ];
    }

    protected $appends = ['available_ports'];

    protected static function booted(): void
    {
        static::saved(function (Odp $odp) {
            // 1. Pastikan OdpPort 1..total_ports tersedia
            if ($odp->total_ports > 0) {
                for ($p = 1; $p <= $odp->total_ports; $p++) {
                    \App\Models\OdpPort::firstOrCreate(
                        ['odp_id' => $odp->id, 'port_number' => $p],
                        ['status' => 'available']
                    );
                }
            }

            // 2. Sinkronkan total used_ports & status ODP
            $usedCount = \App\Models\Ont::where('odp_id', $odp->id)
                ->whereNotIn('status', ['in_stock', 'damaged', 'maintenance', 'returned'])
                ->count();
            $newStatus = ($odp->total_ports > 0 && $usedCount >= $odp->total_ports) 
                ? 'full' 
                : ($odp->status === 'full' ? 'active' : $odp->status);

            if ($odp->used_ports !== $usedCount || $odp->status !== $newStatus) {
                $odp->newQuery()->where('id', $odp->id)->update([
                    'used_ports' => $usedCount,
                    'status' => $newStatus,
                ]);
            }

            // 3. Jika Area ODP berubah, update area seluruh ONT di bawahnya
            if ($odp->wasChanged('area_id') && $odp->area_id) {
                \App\Models\Ont::where('odp_id', $odp->id)->update(['area_id' => $odp->area_id]);
            }
        });

        static::deleting(function (Odp $odp) {
            // 1. Lepas semua ONT yang terhubung
            foreach ($odp->onts as $ont) {
                $ont->update([
                    'odp_id' => null,
                    'odp_port_id' => null,
                    'status' => 'Belum Set/Baru Input',
                    'port_number' => null,
                ]);
            }

            // 2. Putuskan relasi dari survey
            $surveyUpdate = ['odp_id' => null];
            if (\Illuminate\Support\Facades\Schema::hasColumn('surveys', 'port_number')) {
                $surveyUpdate['port_number'] = null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('surveys', 'odp_port_id')) {
                $surveyUpdate['odp_port_id'] = null;
            }
            \App\Models\Survey::where('odp_id', $odp->id)->update($surveyUpdate);

            // 3. Reset parent_odp_id pada ODP anak (jika split)
            static::where('parent_odp_id', $odp->id)->update(['parent_odp_id' => null]);

            // 4. Hapus port ODP
            $odp->ports()->delete();

            // 5. Hapus file foto dari storage
            if ($odp->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($odp->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($odp->photo);
            }
        });
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

    public function ports(): HasMany
    {
        return $this->hasMany(OdpPort::class);
    }

    /**
     * ODP dimiliki oleh satu Area (N:1)
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * ODP memiliki banyak Survey (1:N)
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    /**
     * ODP Induk (jika split lanjutan)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Odp::class, 'parent_odp_id');
    }

    /**
     * ODP Lanjutan/Cabang (jika ODP induk split)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Odp::class, 'parent_odp_id');
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
