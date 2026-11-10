<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class CustomerSocializationVisit extends Model
{
    use HasFactory;

    protected $table = 'customer_socialization_visits';

    protected $fillable = [
        'visit_number',
        'customer_id',
        'area_id',
        'odp_id',
        'purpose_category',
        'title',
        'issue_summary',
        'admin_notes',
        'scheduled_date',
        'scheduled_time',
        'priority',
        'status',
        'start_time',
        'end_time',
        'met_with_name',
        'met_with_relation',
        'customer_response',
        'socialization_points',
        'report_notes',
        'follow_up_action',
        'photo_visit',
        'latitude',
        'longitude',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date:Y-m-d',
            'completed_at' => 'datetime',
            'socialization_points' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    protected $appends = [
        'photo_visit_urls',
        'purpose_label',
        'response_label',
    ];

    public static function generateVisitNumber(): string
    {
        $prefix = 'SOS-' . date('Ym');
        $last = self::where('visit_number', 'LIKE', "{$prefix}-%")
            ->orderByDesc('id')
            ->first();

        if ($last && preg_match('/' . preg_quote($prefix, '/') . '-(\d+)/', $last->visit_number, $matches)) {
            $nextSeq = str_pad((int) $matches[1] + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextSeq = '0001';
        }

        return "{$prefix}-{$nextSeq}";
    }

    public function getPhotoVisitUrlsAttribute(): array
    {
        if (empty($this->photo_visit)) {
            return [];
        }
        $decoded = json_decode($this->photo_visit, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(function ($p) {
                return $p ? Storage::url($p) : null;
            }, $decoded)));
        }
        return [Storage::url($this->photo_visit)];
    }

    public function getPurposeLabelAttribute(): string
    {
        return match ($this->purpose_category) {
            'gangguan_berulang' => 'Sosialisasi & Penanganan Gangguan Berulang',
            'pasca_perbaikan_jalur' => 'Edukasi Pasca Peremajaan / Perbaikan Jalur ODP',
            'edukasi_perangkat' => 'Edukasi Penggunaan Perangkat ONT & WiFi Rumah',
            'komplain_khusus' => 'Kunjungan Khusus Care & Mediasi Keluhan',
            'service_recovery' => 'Service Recovery & Pengecekan Kepuasan Pelanggan',
            default => 'Kunjungan Sosialisasi Pelanggan',
        };
    }

    public function getResponseLabelAttribute(): string
    {
        return match ($this->customer_response) {
            'puas_memahami' => 'Puas & Memahami Penjelasan Petugas',
            'minta_pantauan' => 'Memahami & Minta Pantauan Koneksi Lanjutan',
            'masih_keluhan' => 'Masih Ada Keluhan / Perlu Tindak Lanjut Teknis',
            'butuh_ganti_perangkat' => 'Perlu Pergantian Perangkat / Perbaikan Jalur',
            default => '-',
        };
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_socialization_visit_user', 'visit_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}

