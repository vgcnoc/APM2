<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'wifi_ssid',
        'wifi_password',
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

    protected $appends = [
        'resolved_hotspot_vlan_id',
        'resolved_vlan_id',
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
    public bool $skipStockReturnOnDelete = false;

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
                $customerId = $ont->customer_id;
                $oldPppoe = $ont->getOriginal('pppoe_user');
                $newPppoe = $ont->pppoe_user;
                $oldHotspot = $ont->getOriginal('hotspot_user');
                $newHotspot = $ont->hotspot_user;

                try {
                    if ($ont->wasChanged('area_id') && $ont->area_id) {
                        $customer = $ont->customer;
                        if ($customer && $customer->area_id !== $ont->area_id) {
                            $customer->update(['area_id' => $ont->area_id]);
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Ont::saved] Area sync error: ' . $e->getMessage());
                }

                // Jalankan sinkronisasi RADIUS setelah transaksi DB commit agar tidak memicu CoA/cross-connection lock di dalam transaksi
                \Illuminate\Support\Facades\DB::afterCommit(function () use ($customerId, $oldPppoe, $newPppoe, $oldHotspot, $newHotspot) {
                    try {
                        $customer = \App\Models\Customer::find($customerId);
                        if (!$customer) {
                            return;
                        }

                        $radius = app(\App\Services\RadiusService::class);
                        if (!$radius || !$radius->enabled()) {
                            return;
                        }

                        if ($oldPppoe && $oldPppoe !== $newPppoe) {
                            $radius->guard(function ($rad) use ($oldPppoe) {
                                $rad->removeUser($oldPppoe);
                                $rad->disconnect($oldPppoe);
                            });
                        }
                        if ($oldHotspot && $oldHotspot !== $newHotspot) {
                            $radius->guard(function ($rad) use ($oldHotspot) {
                                $rad->removeUser($oldHotspot);
                                $rad->disconnect($oldHotspot);
                            });
                        }

                        // Jangan jalankan syncCustomer/CoA disconnect pada pelanggan tahap pra-aktivasi (booking/survey/installing)
                        if (!in_array($customer->status, ['booking', 'survey', 'installing'], true)) {
                            $radius->syncCustomer($customer);
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('[Ont::saved] Sync error: ' . $e->getMessage());
                    }
                });
            }
        });

        static::deleting(function ($ont) {
            // 1. Ketika ONT dihapus bukan edit, maka data pelanggan serta yang berhubungan ikut terhapus (Requirement 1)
            $restoredViaCustomerDelete = false;
            if ($ont->customer_id && !static::$isCascadingCustomerDelete && !\App\Models\Customer::$isPurging) {
                $customer = \App\Models\Customer::find($ont->customer_id);
                if ($customer) {
                    static::$isCascadingCustomerDelete = true;
                    try {
                        $customer->delete();
                        $restoredViaCustomerDelete = true;
                    } finally {
                        static::$isCascadingCustomerDelete = false;
                    }
                }
            }

            // 2. Hapus akun FreeRADIUS PPPoE dan Hotspot setelah commit transaksi
            $pppoeUser = $ont->pppoe_user;
            $hotspotUser = $ont->hotspot_user;
            if ($pppoeUser || $hotspotUser) {
                \Illuminate\Support\Facades\DB::afterCommit(function () use ($pppoeUser, $hotspotUser) {
                    try {
                        $radius = app(\App\Services\RadiusService::class);
                        if ($radius && $radius->enabled()) {
                            $radius->guard(function ($rad) use ($pppoeUser, $hotspotUser) {
                                if ($pppoeUser) {
                                    $rad->removeUser($pppoeUser);
                                    $rad->disconnect($pppoeUser);
                                }
                                if ($hotspotUser) {
                                    $rad->removeUser($hotspotUser);
                                    $rad->disconnect($hotspotUser);
                                }
                            });
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }
                });
            }

            // 3. Kembalikan unit ONT ke stok Gudang HANYA jika ONT sebelumnya terpasang di pelanggan (customer_id ada)
            // dan belum dipulihkan oleh penghapusan Customer (purgeConnectedData).
            // ONT yang masih di gudang (customer_id == null) SUDAH terhitung di stok gudang sehingga tidak boleh ditambah lagi!
            $wasInstalledOnCustomer = !empty($ont->getOriginal('customer_id')) || !empty($ont->customer_id);
            $isDamaged = in_array(strtolower((string)$ont->status), ['rusak', 'damaged']);
            if (
                $wasInstalledOnCustomer
                && !$restoredViaCustomerDelete
                && !$ont->skipStockReturnOnDelete
                && !\App\Models\Customer::$isPurging
                && !static::$isCascadingCustomerDelete
                && !$isDamaged
            ) {
                try {
                    $mat = \App\Services\MaterialService::resolveOntMaterial($ont);
                    if ($mat) {
                        \App\Services\CustomerMaterialService::returnMaterialToWarehouse(
                            $mat,
                            1,
                            $ont->area_id,
                            'pcs'
                        );
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Ont::deleting] Error returning ONT stock to warehouse: ' . $e->getMessage());
                }
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

    /**
     * Relasi Tiket PGS dimana ONT ini disiapkan sebagai unit pengganti
     */
    public function pgsTicketsPrepared(): HasMany
    {
        return $this->hasMany(PgsTicket::class, 'prepared_ont_id');
    }

    /**
     * Relasi Tiket PGS dimana ONT ini ditarik/diganti dari pelanggan
     */
    public function pgsTicketsReplaced(): HasMany
    {
        return $this->hasMany(PgsTicket::class, 'old_ont_id');
    }

    /**
     * Relasi Tiket PGK dimana ONT ini disiapkan sebagai unit pengganti
     */
    public function pgkTicketsPrepared(): HasMany
    {
        return $this->hasMany(PgkTicket::class, 'prepared_ont_id');
    }

    /**
     * Relasi Tiket PGK dimana ONT ini ditarik/diganti dari pelanggan
     */
    public function pgkTicketsReplaced(): HasMany
    {
        return $this->hasMany(PgkTicket::class, 'old_ont_id');
    }

    /**
     * Relasi serah terima barang gudang (ONT ditarik/lama)
     */
    public function materialTransactionsOld(): HasMany
    {
        return $this->hasMany(MaterialTransaction::class, 'old_ont_id');
    }

    /**
     * Relasi serah terima barang gudang (ONT pengganti)
     */
    public function materialTransactionsReplacement(): HasMany
    {
        return $this->hasMany(MaterialTransaction::class, 'replacement_ont_id');
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

    /**
     * Scope untuk unit ONT siap pakai dari stok gudang:
     * - Khusus status 'Sudah Set'
     * - Belum terpasang di pelanggan (customer_id is null & tidak terhubung ke relasi customer)
     * - Tidak sedang terpasang di data pelanggan manapun (customers.ont_id)
     * - Tidak sedang disiapkan/dibekalkan pada tiket aktif PGK / PGS
     */
    public function scopeAvailableForAssignment($query, ?int $areaId = null)
    {
        $reservedPgk = class_exists(\App\Models\PgkTicket::class)
            ? \App\Models\PgkTicket::whereNotNull('prepared_ont_id')->whereIn('status', ['scheduled', 'in_progress'])->pluck('prepared_ont_id')
            : collect();
        $reservedPgs = class_exists(\App\Models\PgsTicket::class)
            ? \App\Models\PgsTicket::whereNotNull('prepared_ont_id')->whereIn('status', ['scheduled', 'in_progress'])->pluck('prepared_ont_id')
            : collect();

        $excludeIds = $reservedPgk->merge($reservedPgs)->filter()->unique()->toArray();

        $query->where('status', 'Sudah Set')
            ->whereNull('customer_id')
            ->whereDoesntHave('customer');

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        if ($areaId) {
            $query->where('area_id', $areaId);
        }

        return $query;
    }

    public function scopeWithFullTopology($query)
    {
        return $query->with([
            'odp.odc.olt', 
            'customer.sales.employee', 
            'customer.technicianSchedules' => function ($q) {
                $q->where('type', 'installation');
            },
            'area'
        ]);
    }

    /**
     * Scope untuk ONT yang diganti dari proses perbaikan/komplain (PGS/PGK/Handover)
     */
    public function scopeDiganti($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('pgsTicketsReplaced', function ($tq) {
                $tq->where('ont_replacement_status', 'installed')
                   ->orWhere('ont_replacement_action', 'installed');
            })
            ->orWhereHas('pgkTicketsReplaced', function ($tq) {
                $tq->where('ont_replacement_status', 'installed')
                   ->orWhere('ont_replacement_action', 'installed');
            })
            ->orWhereHas('materialTransactionsOld')
            ->orWhere('description', 'like', '%Diganti dengan SN%')
            ->orWhere('description', 'like', '%Ditarik dari%');
        });
    }

    /**
     * Scope untuk ONT yang bertindak sebagai unit pengganti pada proses PGS/PGK/Handover
     */
    public function scopePengganti($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('pgsTicketsPrepared', function ($tq) {
                $tq->whereIn('ont_replacement_status', ['prepared', 'installed'])
                   ->where(function ($sub) {
                       $sub->whereNull('ont_replacement_action')
                           ->orWhere('ont_replacement_action', '!=', 'cancelled');
                   });
            })
            ->orWhereHas('pgkTicketsPrepared', function ($tq) {
                $tq->whereIn('ont_replacement_status', ['prepared', 'installed'])
                   ->where(function ($sub) {
                       $sub->whereNull('ont_replacement_action')
                           ->orWhere('ont_replacement_action', '!=', 'cancelled');
                   });
            })
            ->orWhereHas('materialTransactionsReplacement')
            ->orWhere('status', 'Disiapkan Teknisi')
            ->orWhere('description', 'like', '%Menggantikan SN%');
        });
    }

    /**
     * Format informasi penggantian cerdas terkait tiket PGS/PGK
     */
    public function getReplacementInfo(): ?array
    {
        $isValidReplaced = function ($t) {
            return $t && (
                $t->ont_replacement_status === 'installed' || 
                $t->ont_replacement_action === 'installed'
            );
        };

        $isValidPrepared = function ($t) {
            return $t && (
                in_array($t->ont_replacement_status, ['prepared', 'installed']) &&
                $t->ont_replacement_action !== 'cancelled' &&
                $t->ont_replacement_action !== 'rejected'
            );
        };

        $pgsOld = $this->relationLoaded('pgsTicketsReplaced') 
            ? $this->pgsTicketsReplaced->filter($isValidReplaced)->sortByDesc('id')->first() 
            : $this->pgsTicketsReplaced()->where(function ($q) {
                $q->where('ont_replacement_status', 'installed')
                  ->orWhere('ont_replacement_action', 'installed');
            })->latest('id')->first();
            
        $pgkOld = $this->relationLoaded('pgkTicketsReplaced') 
            ? $this->pgkTicketsReplaced->filter($isValidReplaced)->sortByDesc('id')->first() 
            : $this->pgkTicketsReplaced()->where(function ($q) {
                $q->where('ont_replacement_status', 'installed')
                  ->orWhere('ont_replacement_action', 'installed');
            })->latest('id')->first();

        $pgsPrep = $this->relationLoaded('pgsTicketsPrepared') 
            ? $this->pgsTicketsPrepared->filter($isValidPrepared)->sortByDesc('id')->first() 
            : $this->pgsTicketsPrepared()->where(function ($q) {
                $q->whereIn('ont_replacement_status', ['prepared', 'installed'])
                  ->where(function ($sub) {
                      $sub->whereNull('ont_replacement_action')
                          ->orWhere('ont_replacement_action', '!=', 'cancelled');
                  });
            })->latest('id')->first();
            
        $pgkPrep = $this->relationLoaded('pgkTicketsPrepared') 
            ? $this->pgkTicketsPrepared->filter($isValidPrepared)->sortByDesc('id')->first() 
            : $this->pgkTicketsPrepared()->where(function ($q) {
                $q->whereIn('ont_replacement_status', ['prepared', 'installed'])
                  ->where(function ($sub) {
                      $sub->whereNull('ont_replacement_action')
                          ->orWhere('ont_replacement_action', '!=', 'cancelled');
                  });
            })->latest('id')->first();

        $latestOld = null;
        if ($pgsOld && $pgkOld) {
            $latestOld = ($pgsOld->created_at >= $pgkOld->created_at) 
                ? ['ticket' => $pgsOld, 'type' => 'PGS'] 
                : ['ticket' => $pgkOld, 'type' => 'PGK'];
        } elseif ($pgsOld) {
            $latestOld = ['ticket' => $pgsOld, 'type' => 'PGS'];
        } elseif ($pgkOld) {
            $latestOld = ['ticket' => $pgkOld, 'type' => 'PGK'];
        }

        $latestPrep = null;
        if ($pgsPrep && $pgkPrep) {
            $latestPrep = ($pgsPrep->created_at >= $pgkPrep->created_at) 
                ? ['ticket' => $pgsPrep, 'type' => 'PGS'] 
                : ['ticket' => $pgkPrep, 'type' => 'PGK'];
        } elseif ($pgsPrep) {
            $latestPrep = ['ticket' => $pgsPrep, 'type' => 'PGS'];
        } elseif ($pgkPrep) {
            $latestPrep = ['ticket' => $pgkPrep, 'type' => 'PGK'];
        }

        if (!$latestOld && !$latestPrep) {
            if (!$this->customer_id && !empty($this->description) && str_contains($this->description, 'Diganti dengan SN')) {
                return [
                    'role' => 'replaced',
                    'label' => 'ONT Diganti',
                    'ticket_type' => str_contains($this->description, 'PGK') ? 'PGK' : 'PGS',
                    'ticket_number' => null,
                    'notes' => $this->description,
                    'is_fallback' => true,
                ];
            }
            if (!empty($this->description) && str_contains($this->description, 'Menggantikan SN')) {
                return [
                    'role' => 'replacement',
                    'label' => 'ONT Pengganti',
                    'ticket_type' => str_contains($this->description, 'PGK') ? 'PGK' : 'PGS',
                    'ticket_number' => null,
                    'notes' => $this->description,
                    'is_fallback' => true,
                ];
            }
            if ($this->status === 'Disiapkan Teknisi') {
                return [
                    'role' => 'replacement',
                    'label' => 'ONT Pengganti',
                    'ticket_type' => 'PGS/PGK',
                    'ticket_number' => null,
                    'status' => 'prepared',
                    'notes' => 'Unit siap pasang sedang dibekalkan ke teknisi lapangan.',
                    'is_fallback' => true,
                ];
            }
            return null;
        }

        $res = [
            'is_replaced' => !empty($latestOld),
            'is_replacement' => !empty($latestPrep),
        ];

        // Jika terlibat di kedua peran atau tentukan mana yang terbaru/relevan
        if ($latestPrep && (!$latestOld || ($latestPrep['ticket']->created_at >= $latestOld['ticket']->created_at))) {
            $t = $latestPrep['ticket'];
            $res['role'] = 'replacement';
            $res['label'] = 'ONT Pengganti';
            $res['ticket_type'] = $latestPrep['type'];
            $res['ticket_id'] = $t->id;
            $res['ticket_number'] = $t->ticket_number;
            $res['ticket_url'] = '/' . strtolower($latestPrep['type']) . '?search=' . urlencode($t->ticket_number);
            $res['ticket_status'] = $t->status;
            $res['replacement_status'] = $t->ont_replacement_status;
            $res['replacement_action'] = $t->ont_replacement_action;
            $res['target_customer_name'] = $t->customer?->name;
            $res['target_customer_code'] = $t->customer?->customer_code ?: $t->customer?->cid;
            $res['partner_ont_sn'] = $t->oldOnt?->serial_number;
            $res['partner_ont_model'] = trim(($t->oldOnt?->brand ?? '') . ' ' . ($t->oldOnt?->model ?? ''));
            $res['technician_name'] = $t->preparedByUser?->name ?: $t->requestedByUser?->name;
            $res['notes'] = $t->ont_replacement_notes ?: $t->requested_ont_notes;
            $res['date'] = $t->prepared_ont_at ? $t->prepared_ont_at->format('d M Y H:i') : ($t->updated_at ? $t->updated_at->format('d M Y H:i') : null);
        } else {
            $t = $latestOld['ticket'];
            $res['role'] = 'replaced';
            $res['label'] = 'ONT Diganti';
            $res['ticket_type'] = $latestOld['type'];
            $res['ticket_id'] = $t->id;
            $res['ticket_number'] = $t->ticket_number;
            $res['ticket_url'] = '/' . strtolower($latestOld['type']) . '?search=' . urlencode($t->ticket_number);
            $res['ticket_status'] = $t->status;
            $res['replacement_status'] = $t->ont_replacement_status;
            $res['replacement_action'] = $t->ont_replacement_action;
            $res['origin_customer_name'] = $t->customer?->name;
            $res['origin_customer_code'] = $t->customer?->customer_code ?: $t->customer?->cid;
            $res['partner_ont_sn'] = $t->preparedOnt?->serial_number;
            $res['partner_ont_model'] = trim(($t->preparedOnt?->brand ?? '') . ' ' . ($t->preparedOnt?->model ?? ''));
            $res['technician_name'] = $t->requestedByUser?->name ?: $t->preparedByUser?->name;
            $res['notes'] = $t->ont_replacement_notes ?: $t->requested_ont_notes;
            $res['date'] = $t->prepared_ont_at ? $t->prepared_ont_at->format('d M Y H:i') : ($t->updated_at ? $t->updated_at->format('d M Y H:i') : null);
        }

        return $res;
    }

    /**
     * Dapatkan VLAN ID Hotspot otomatis dari konfigurasi OLT PON topologi jaringan
     */
    public function resolveHotspotVlanId(): ?string
    {
        if (!empty($this->hotspot_vlan_id)) {
            return (string) $this->hotspot_vlan_id;
        }

        $odp = $this->relationLoaded('odp') ? $this->odp : null;
        if (!$odp && $this->relationLoaded('customer') && $this->customer) {
            $odp = $this->customer->relationLoaded('surveys') ? $this->customer->surveys?->first()?->odp : null;
        }
        if (!$odp && $this->odp_id) {
            $odp = $this->odp;
        }

        if ($odp) {
            $odc = $odp->relationLoaded('odc') ? $odp->odc : $odp->odc;
            if ($odc) {
                $olt = $odc->relationLoaded('olt') ? $odc->olt : $odc->olt;
                $ponPort = $odc->pon_port;

                if ($olt && $ponPort && !empty($olt->pon_vlans)) {
                    $ponVlans = is_string($olt->pon_vlans) ? json_decode($olt->pon_vlans, true) : $olt->pon_vlans;
                    if (is_array($ponVlans)) {
                        $portConfig = collect($ponVlans)->first(fn($p) => ($p['port'] ?? null) == $ponPort);
                        if ($portConfig && !empty($portConfig['vlans']) && is_array($portConfig['vlans'])) {
                            foreach ($portConfig['vlans'] as $vlan) {
                                $mode = strtoupper($vlan['access_mode'] ?? '');
                                $name = strtolower($vlan['name'] ?? '');
                                if ($mode === 'HOTSPOT' || str_contains($name, 'hotspot')) {
                                    if (!empty($vlan['vlan_id'])) {
                                        return (string) $vlan['vlan_id'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Dapatkan VLAN ID PPPoE otomatis dari konfigurasi OLT PON topologi jaringan
     */
    public function resolvePppoeVlanId(): ?string
    {
        if (!empty($this->vlan_id)) {
            return (string) $this->vlan_id;
        }

        $odp = $this->relationLoaded('odp') ? $this->odp : null;
        if (!$odp && $this->relationLoaded('customer') && $this->customer) {
            $odp = $this->customer->relationLoaded('surveys') ? $this->customer->surveys?->first()?->odp : null;
        }
        if (!$odp && $this->odp_id) {
            $odp = $this->odp;
        }

        if ($odp) {
            $odc = $odp->relationLoaded('odc') ? $odp->odc : $odp->odc;
            if ($odc) {
                $olt = $odc->relationLoaded('olt') ? $odc->olt : $odc->olt;
                $ponPort = $odc->pon_port;

                if ($olt && $ponPort && !empty($olt->pon_vlans)) {
                    $ponVlans = is_string($olt->pon_vlans) ? json_decode($olt->pon_vlans, true) : $olt->pon_vlans;
                    if (is_array($ponVlans)) {
                        $portConfig = collect($ponVlans)->first(fn($p) => ($p['port'] ?? null) == $ponPort);
                        if ($portConfig && !empty($portConfig['vlans']) && is_array($portConfig['vlans'])) {
                            foreach ($portConfig['vlans'] as $vlan) {
                                $mode = strtoupper($vlan['access_mode'] ?? '');
                                $name = strtolower($vlan['name'] ?? '');
                                if ($mode === 'PPPOE' || str_contains($name, 'pppoe')) {
                                    if (!empty($vlan['vlan_id'])) {
                                        return (string) $vlan['vlan_id'];
                                    }
                                }
                            }
                        }
                    }
                }

                if ($olt && !empty($olt->default_vlan)) {
                    return (string) $olt->default_vlan;
                }
            }
        }

        return null;
    }

    public function getResolvedHotspotVlanIdAttribute(): ?string
    {
        return $this->resolveHotspotVlanId();
    }

    public function getResolvedVlanIdAttribute(): ?string
    {
        return $this->resolvePppoeVlanId();
    }
}
