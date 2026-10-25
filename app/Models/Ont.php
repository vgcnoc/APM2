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
        'description',
        'material_transaction_item_id',
        'start_time',
        'end_time',
        'photo_odp',
        'photo_installation',
        'photo_ont',
        'photo_customer',
        'photo_redaman',
        'initial_rx_power',
        'last_rx_updated_at',
        'last_rx_ticket_ref',
        'last_rx_notes',
    ];

    protected function casts(): array
    {
        return [
            'port_number' => 'integer',
            'rx_power' => 'decimal:2',
            'initial_rx_power' => 'decimal:2',
            'last_rx_updated_at' => 'datetime',
            'tx_power' => 'decimal:2',
            'input_officers' => 'array',
        ];
    }

    public function setInitialRxPowerAttribute($value): void
    {
        if ($value === null || $value === '' || $value === 'null') {
            $this->attributes['initial_rx_power'] = null;
            return;
        }

        $parsed = \App\Services\DataMigrationService::parseRxPower($value);
        $this->attributes['initial_rx_power'] = number_format($parsed, 2, '.', '');
    }

    public function setRxPowerAttribute($value): void
    {
        if ($value === null || $value === '' || $value === 'null') {
            $this->attributes['rx_power'] = null;
            return;
        }

        $parsed = \App\Services\DataMigrationService::parseRxPower($value);
        $this->attributes['rx_power'] = number_format($parsed, 2, '.', '');
    }

    public function setTxPowerAttribute($value): void
    {
        if ($value === null || $value === '' || $value === 'null') {
            $this->attributes['tx_power'] = null;
            return;
        }

        $str = trim(str_replace(['dBm', 'dbm', ' '], '', (string)$value));
        $str = str_replace(',', '.', $str);
        if (is_numeric($str)) {
            $this->attributes['tx_power'] = number_format((float)$str, 2, '.', '');
        } else {
            $this->attributes['tx_power'] = null;
        }
    }

    public static bool $isCascadingCustomerDelete = false;

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

        static::saved(function ($ont) {
            $oldOdpId = $ont->getOriginal('odp_id');
            $oldPort = $ont->getOriginal('port_number');
            $newOdpId = $ont->odp_id;
            $newPort = $ont->port_number;

            // Release old port if changed or if ONT is in stock/damaged
            if ($oldOdpId && $oldPort && ($oldOdpId != $newOdpId || $oldPort != $newPort || in_array($ont->status, ['in_stock', 'damaged', 'maintenance', 'returned']))) {
                \App\Models\OdpPort::where('odp_id', $oldOdpId)
                    ->where('port_number', $oldPort)
                    ->update(['status' => 'available', 'description' => null]);

                $oldOdp = \App\Models\Odp::find($oldOdpId);
                if ($oldOdp) {
                    $used = static::where('odp_id', $oldOdpId)->where('id', '!=', $ont->id)->count();
                    $oldOdp->update([
                        'used_ports' => $used,
                        'status' => ($used >= $oldOdp->total_ports) ? 'full' : 'active',
                    ]);
                }
            }

            // Mark new port as used if assigned and active
            if ($newOdpId && $newPort && !in_array($ont->status, ['in_stock', 'damaged', 'maintenance', 'returned'])) {
                \App\Models\OdpPort::where('odp_id', $newOdpId)
                    ->where('port_number', $newPort)
                    ->update(['status' => 'used']);

                $newOdp = \App\Models\Odp::find($newOdpId);
                if ($newOdp) {
                    $used = static::where('odp_id', $newOdpId)->count();
                    $newOdp->update([
                        'used_ports' => $used,
                        'status' => ($used >= $newOdp->total_ports) ? 'full' : 'active',
                    ]);
                }
            }

            // Sinkronisasi data edit: Jika kredensial PPPoE/Hotspot diedit di ONT, sinkronkan ke FreeRADIUS & Customer (Requirement 4)
            if ($ont->customer_id) {
                try {
                    $customer = $ont->customer;
                    if ($customer) {
                        $radius = app(\App\Services\RadiusService::class);
                        if ($radius && $radius->enabled()) {
                            $oldPppoe = $ont->getOriginal('pppoe_user');
                            if ($oldPppoe && $oldPppoe !== $ont->pppoe_user) {
                                $radius->guard(function ($rad) use ($oldPppoe) {
                                    $rad->removeUser($oldPppoe);
                                    $rad->disconnect($oldPppoe);
                                });
                            }
                            $oldHotspot = $ont->getOriginal('hotspot_user');
                            if ($oldHotspot && $oldHotspot !== $ont->hotspot_user) {
                                $radius->guard(function ($rad) use ($oldHotspot) {
                                    $rad->removeUser($oldHotspot);
                                    $rad->disconnect($oldHotspot);
                                });
                            }
                            $radius->syncCustomer($customer);
                        }

                        // Sinkronisasi area_id jika berbeda
                        if ($ont->wasChanged('area_id') && $ont->area_id && $customer->area_id !== $ont->area_id) {
                            $customer->update(['area_id' => $ont->area_id]);
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Ont::saved] Sync error: ' . $e->getMessage());
                }
            }
        });

        static::deleting(function ($ont) {
            // 1. Ketika ONT dihapus bukan edit, maka data pelanggan serta yang berhubungan ikut terhapus (Requirement 1)
            if ($ont->customer_id && !static::$isCascadingCustomerDelete && !\App\Models\Customer::$isPurging) {
                $customer = \App\Models\Customer::find($ont->customer_id);
                if ($customer) {
                    static::$isCascadingCustomerDelete = true;
                    try {
                        $customer->delete();
                    } finally {
                        static::$isCascadingCustomerDelete = false;
                    }
                }
            }

            // 2. Hapus akun FreeRADIUS PPPoE dan Hotspot jika ada
            try {
                $radius = app(\App\Services\RadiusService::class);
                if ($radius && $radius->enabled()) {
                    $radius->guard(function ($rad) use ($ont) {
                        if ($ont->pppoe_user) {
                            $rad->removeUser($ont->pppoe_user);
                            $rad->disconnect($ont->pppoe_user);
                        }
                        if ($ont->hotspot_user) {
                            $rad->removeUser($ont->hotspot_user);
                            $rad->disconnect($ont->hotspot_user);
                        }
                    });
                }
            } catch (\Throwable $e) {
                // ignore
            }

            // 3. Kembalikan unit ONT ke stok Gudang (Requirement 1)
            try {
                $matItem = $ont->material_transaction_item_id 
                    ? \App\Models\MaterialTransactionItem::with('material')->find($ont->material_transaction_item_id)
                    : null;

                if ($matItem && $matItem->material) {
                    \App\Services\CustomerMaterialService::returnMaterialToWarehouse(
                        $matItem->material,
                        1,
                        $ont->area_id,
                        'unit'
                    );
                } else {
                    // Cari material ONT yang sesuai di tabel materials
                    $mat = \App\Models\Material::where('category', 'like', '%ont%')
                        ->orWhere('category', 'like', '%modem%')
                        ->orWhere('name', 'like', "%{$ont->brand}%")
                        ->first();
                    if ($mat) {
                        \App\Services\CustomerMaterialService::returnMaterialToWarehouse(
                            $mat,
                            1,
                            $ont->area_id,
                            'unit'
                        );
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Ont::deleting] Error returning ONT stock to warehouse: ' . $e->getMessage());
            }

            // 4. Release ODP Port & sinkronisasi counter ODP
            if ($ont->odp_id && $ont->port_number) {
                \App\Models\OdpPort::where('odp_id', $ont->odp_id)
                    ->where('port_number', $ont->port_number)
                    ->update(['status' => 'available', 'description' => null]);

                $odp = \App\Models\Odp::find($ont->odp_id);
                if ($odp) {
                    $used = static::where('odp_id', $ont->odp_id)->where('id', '!=', $ont->id)->count();
                    $odp->update([
                        'used_ports' => $used,
                        'status' => ($used >= $odp->total_ports) ? 'full' : 'active',
                    ]);
                }
            }

            // 5. Update MaterialTransactionItem jika terdaftar dari transaksi material
            if ($ont->material_transaction_item_id) {
                $remaining = static::where('material_transaction_item_id', $ont->material_transaction_item_id)
                    ->where('id', '!=', $ont->id)
                    ->count();
                if ($remaining === 0) {
                    \App\Models\MaterialTransactionItem::where('id', $ont->material_transaction_item_id)
                        ->update(['is_registered_to_ont' => false]);
                }
            }

            // 6. Hapus file foto terkait dari storage
            $photos = ['photo_odp', 'photo_installation', 'photo_ont', 'photo_customer', 'photo_redaman'];
            foreach ($photos as $p) {
                try {
                    if ($ont->{$p} && \Illuminate\Support\Facades\Storage::disk('public')->exists($ont->{$p})) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($ont->{$p});
                    }
                } catch (\Throwable $e) {
                    // ignore
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
