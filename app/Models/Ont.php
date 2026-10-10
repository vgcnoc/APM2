<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ont extends Model
{
    use HasFactory;

    protected $fillable = [
        'ont_id',
        'odp_id',
        'odp_port_id',
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
        'free_hotspot',
        'hotspot_user',
        'hotspot_password',
        'hotspot_vlan_id',
        'input_officers',
        'brand',
        'model',
        'port_number',
        'rx_power',
        'tx_power',
        'status',
        'description',
        'material_transaction_item_id',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ont) {
            if (empty($ont->ont_id)) {
                $lastOnt = static::whereNotNull('ont_id')
                                 ->where('ont_id', 'like', 'V%')
                                 ->orderByRaw('CAST(SUBSTRING(ont_id, 2) AS UNSIGNED) DESC')
                                 ->first();
                
                if ($lastOnt) {
                    $lastNumber = (int) substr($lastOnt->ont_id, 1);
                    $ont->ont_id = 'V' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                } else {
                    $ont->ont_id = 'V1001';
                }
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────

    /**
     * ONT terhubung ke satu ODP (N:1)
     */
    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(OdpPort::class, 'odp_port_id');
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
        return $query->with(['odp.odc.olt', 'customer.sales', 'area']);
    }
}
