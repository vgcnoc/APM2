<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'phone',
        'address',
        'area',
        'identity_photo',
        'latitude',
        'longitude',
        'package_id',
        'is_reseller',
        'status',
        'registration_date',
        'activation_date',
        'notes',
        'base_amount',
        'installation_fee',
        'sales_id',
        'is_audited',
        'area_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'registration_date' => 'date:Y-m-d',
            'activation_date' => 'date:Y-m-d',
            'is_audited' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    /**
     * Area tempat pelanggan berada
     */
    public function areaModel(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Paket internet yang diambil pelanggan
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'package_id');
    }

    /**
     * Sales / Marketing yang mendaftarkan pelanggan
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    /**
     * ONT yang terpasang di pelanggan (1:1 secara logika)
     */
    public function ont(): HasOne
    {
        return $this->hasOne(Ont::class);
    }

    /**
     * Riwayat invoice pelanggan
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Riwayat pembayaran pelanggan
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Tiket gangguan pelanggan
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Riwayat survey lokasi pelanggan
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    /**
     * Jadwal teknisi untuk pelanggan ini
     */
    public function technicianSchedules(): HasMany
    {
        return $this->hasMany(TechnicianSchedule::class);
    }

    /**
     * Data reseller (jika is_reseller = true)
     */
    public function reseller(): HasOne
    {
        return $this->hasOne(Reseller::class);
    }

    // ── Computed ───────────────────────────────────────────────

    /**
     * Informasi ONT & ODP yang terhubung
     */
    public function getNetworkInfoAttribute(): ?array
    {
        $ont = $this->ont;
        if (!$ont) return null;

        return [
            'ont_sn' => $ont->serial_number,
            'ont_status' => $ont->status,
            'rx_power' => $ont->rx_power,
            'odp' => $ont->odp?->name,
            'odc' => $ont->odp?->odc?->name,
            'olt' => $ont->odp?->odc?->olt?->name,
        ];
    }

    /**
     * Status label yang readable
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'booking' => 'Booking',
            'survey' => 'Proses Survey',
            'installing' => 'Proses Pemasangan',
            'active' => 'Aktif',
            'suspended' => 'Dibekukan',
            'terminated' => 'Berhenti',
            default => $this->status,
        };
    }

    /**
     * Warna badge status
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'booking' => 'yellow',
            'survey' => 'blue',
            'installing' => 'indigo',
            'active' => 'green',
            'suspended' => 'orange',
            'terminated' => 'red',
            default => 'gray',
        };
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBooking($query)
    {
        return $query->whereIn('status', ['booking', 'survey', 'installing', 'active']);
    }

    public function scopeSurvey($query)
    {
        return $query->whereIn('status', ['survey', 'installing', 'active']);
    }

    public function scopeInstalled($query)
    {
        return $query->where('status', 'installing');
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('customer_code', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%");
        });
    }

    // ── Auto-generate customer code ────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->customer_code)) {
                $lastCode = static::orderByDesc('id')->value('customer_code');
                $nextNumber = $lastCode
                    ? (int) substr($lastCode, 4) + 1
                    : 1;
                $customer->customer_code = 'CUS-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }

            if (empty($customer->registration_date)) {
                $customer->registration_date = now()->toDateString();
            }
        });
    }
}
