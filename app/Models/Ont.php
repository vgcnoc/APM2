<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ont extends Model
{
    use HasFactory;

    protected $fillable = [
        'odp_id',
        'customer_id',
        'area_id',
        'serial_number',
        'mac_address',
        'brand',
        'model',
        'port_number',
        'rx_power',
        'tx_power',
        'status',
        'vlan_mode',
        'vlan_id',
        'access_mode',
        'ip_login',
        'login_user',
        'login_password',
        'pppoe_user',
        'pppoe_password',
        'input_officers',
        'brand',
        'model',
        'port_number',
        'rx_power',
        'tx_power',
        'status',
        'description',
        'start_time',
        'end_time',
        'photo_odp',
        'photo_installation',
        'photo_ont',
        'photo_customer',
        'photo_redaman',
    ];

    protected function casts(): array
    {
        return [
            'port_number' => 'integer',
            'rx_power' => 'decimal:2',
            'tx_power' => 'decimal:2',
            'input_officers' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    /**
     * ONT terhubung ke satu ODP (N:1)
     */
    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    /**
     * ONT dimiliki oleh satu Customer (N:1, tapi secara logika 1:1)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * ONT berada pada satu Area (N:1)
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    // ── Computed ───────────────────────────────────────────────

    /**
     * Path topologi lengkap: OLT > ODC > ODP > Port
     */
    public function getTopologyPathAttribute(): string
    {
        $odp = $this->odp;
        $odc = $odp?->odc;
        $olt = $odc?->olt;

        return implode(' > ', array_filter([
            $olt?->name,
            $odc?->name,
            $odp?->name,
            "Port {$this->port_number}",
        ]));
    }

    /**
     * Cek apakah sinyal dalam kondisi baik (rx_power > -25 dBm)
     */
    public function getSignalHealthAttribute(): string
    {
        if ($this->rx_power === null) return 'unknown';
        if ($this->rx_power >= -20) return 'excellent';
        if ($this->rx_power >= -25) return 'good';
        if ($this->rx_power >= -28) return 'fair';
        return 'poor';
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithFullTopology($query)
    {
        return $query->with(['odp.odc.olt', 'customer']);
    }
}
