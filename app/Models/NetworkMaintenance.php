<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class NetworkMaintenance extends Model
{
    use HasFactory;

    protected $table = 'network_maintenances';

    protected $fillable = [
        'maintenance_number',
        'category',
        'title',
        'area_id',
        'odp_id',
        'odc_id',
        'location_name',
        'latitude',
        'longitude',
        'priority',
        'status',
        'scheduled_date',
        'scheduled_time',
        'start_time',
        'end_time',
        'description',
        'action_taken',
        'optical_power_before',
        'optical_power_after',
        'photo_before',
        'photo_after',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $appends = [
        'category_label',
        'category_icon',
        'photo_after_urls',
        'photo_before_urls',
    ];

    /**
     * URL Semua Foto Dokumentasi Lapangan (Mendukung Multi-Foto)
     */
    public function getPhotoAfterUrlsAttribute(): array
    {
        if (empty($this->photo_after)) return [];
        $decoded = json_decode($this->photo_after, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(function ($p) {
                return $p ? \Illuminate\Support\Facades\Storage::url($p) : null;
            }, $decoded)));
        }
        return [\Illuminate\Support\Facades\Storage::url($this->photo_after)];
    }

    /**
     * URL Foto Sebelum Pengerjaan
     */
    public function getPhotoBeforeUrlsAttribute(): array
    {
        if (empty($this->photo_before)) return [];
        $decoded = json_decode($this->photo_before, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(function ($p) {
                return $p ? \Illuminate\Support\Facades\Storage::url($p) : null;
            }, $decoded)));
        }
        return [\Illuminate\Support\Facades\Storage::url($this->photo_before)];
    }

    /**
     * Auto Generate Nomor Pemeliharaan: MNT-YYYYMM-XXXX
     */
    public static function generateMaintenanceNumber(): string
    {
        $prefix = 'MNT-' . date('Ym');
        $last = self::where('maintenance_number', 'LIKE', "{$prefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($last && preg_match('/' . preg_quote($prefix, '/') . '-(\d+)/', $last->maintenance_number, $matches)) {
            $nextSeq = str_pad((int)$matches[1] + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextSeq = '0001';
        }

        return "{$prefix}-{$nextSeq}";
    }

    /**
     * Relasi Wilayah
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Relasi ODP Target
     */
    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    /**
     * Relasi ODC Target
     */
    public function odc(): BelongsTo
    {
        return $this->belongsTo(Odc::class);
    }

    /**
     * Relasi Petugas Teknisi Lapangan (Many-to-Many via Pivot)
     */
    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'network_maintenance_user', 'network_maintenance_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Relasi Persiapan & Penggunaan Material Pemeliharaan Jalur
     */
    public function materials(): HasMany
    {
        return $this->hasMany(NetworkMaintenanceMaterial::class, 'network_maintenance_id');
    }

    /**
     * User Pembuat Tugas
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User yang Menyelesaikan / Validasi
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Label Kategori Bahasa Indonesia
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'perapihan_jalur' => 'Perapihan Jalur & Kabel',
            'pengecekan_odp' => 'Pengecekan Jalur ODP',
            'pengecekan_odc' => 'Pengecekan Jalur ODC',
            'pengecekan_jalur_utama' => 'Pengecekan Jalur Utama (Backbone)',
            'pemeliharaan_rutin' => 'Pemeliharaan Rutin',
            default => 'Pemeliharaan Jalur',
        };
    }

    /**
     * Emoji & Icon Kategori
     */
    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'perapihan_jalur' => '🧹',
            'pengecekan_odp' => '📦',
            'pengecekan_odc' => '🗄️',
            'pengecekan_jalur_utama' => '🛣️',
            default => '🔧',
        };
    }
}

