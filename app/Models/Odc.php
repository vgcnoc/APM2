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
            'pon_port' => 'integer',
            'is_split' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Odc $odc) {
            // 1. Cegah referensi induk ke diri sendiri
            if ($odc->id && $odc->parent_odc_id && (int) $odc->parent_odc_id === (int) $odc->id) {
                $odc->parent_odc_id = null;
            }

            // 2. Pastikan flag is_split konsisten untuk tipe Split atau ODC yang memiliki relasi induk
            if ($odc->type === 'Split' || !empty($odc->parent_odc_id)) {
                $odc->is_split = true;
            }

            // 3. Sinkronkan pon_id otomatis berdasarkan olt_id + pon_port agar Topologi selalu akurat
            if ($odc->olt_id && $odc->pon_port) {
                if ($odc->isDirty(['olt_id', 'pon_port']) || empty($odc->pon_id)) {
                    if (\Illuminate\Support\Facades\Schema::hasTable('olt_pons')) {
                        $pon = \App\Models\OltPon::firstOrCreate(
                            ['olt_id' => $odc->olt_id, 'port_number' => (int) $odc->pon_port],
                            ['name' => 'PON ' . (int) $odc->pon_port, 'capacity' => 128, 'status' => 'active']
                        );
                        $odc->pon_id = $pon->id;
                    }
                }
            } elseif (empty($odc->pon_port)) {
                $odc->pon_id = null;
            }
        });

        static::saved(function (Odc $odc) {
            if ($odc->parent_odc_id) {
                static::where('id', $odc->parent_odc_id)
                    ->where('is_split', false)
                    ->update(['is_split' => true]);
            }
        });

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

    /**
     * ODC Induk (jika unit ini adalah ODC Split Lanjutan)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Odc::class, 'parent_odc_id');
    }

    /**
     * ODC Lanjutan / Cabang (jika unit ini adalah ODC Split Induk)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Odc::class, 'parent_odc_id');
    }

    // ── Computed ───────────────────────────────────────────────

    public function getTotalOdpsAttribute(): int
    {
        return $this->odps()->count();
    }
}
