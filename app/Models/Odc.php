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
        'pon_id',
        'pon_port',
        'area_id',
        'name',
        'type',
        'photo',
        'location',
        'latitude',
        'longitude',
        'capacity',
        'splitter_ratio',
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

    protected static function booted(): void
    {
        static::updated(function (Odc $odc) {
            // Jika Area ODC berubah, update area ODP downstream dan ONT di bawahnya
            if ($odc->wasChanged('area_id') && $odc->area_id) {
                \App\Models\Odp::where('odc_id', $odc->id)->update(['area_id' => $odc->area_id]);
                $odpIds = \App\Models\Odp::where('odc_id', $odc->id)->pluck('id');
                if ($odpIds->isNotEmpty()) {
                    \App\Models\Ont::whereIn('odp_id', $odpIds)->update(['area_id' => $odc->area_id]);
                }
            }
        });

        static::deleting(function (Odc $odc) {
            // 1. Unlink ODP downstream
            \App\Models\Odp::where('odc_id', $odc->id)->update(['odc_id' => null]);

            // 2. Unlink split children
            static::where('parent_odc_id', $odc->id)->update(['parent_odc_id' => null]);

            // 3. Hapus foto dari storage
            if ($odc->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($odc->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($odc->photo);
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────

    /**
     * ODC dimiliki oleh satu OLT (N:1)
     */
    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function pon(): BelongsTo
    {
        return $this->belongsTo(OltPon::class, 'pon_id');
    }

    /**
     * ODC memiliki banyak ODP (1:N)
     */
    public function odps(): HasMany
    {
        return $this->hasMany(Odp::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    // ── Computed ───────────────────────────────────────────────

    public function getTotalOdpsAttribute(): int
    {
        return $this->odps()->count();
    }
}
