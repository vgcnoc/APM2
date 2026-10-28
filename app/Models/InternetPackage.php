<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternetPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'speed_mbps', 'price', 'installation_fee', 'area_installation_fees', 'access_mode', 'description', 'is_active', 'is_visible_to_sales',
        'color', 'is_promo', 'promo_price', 'is_mikrotik_group_custom', 'mikrotik_group',
        'is_mikrotik_address_list_custom', 'mikrotik_address_list', 'shared_device',
        'rate_limit', 'active_period', 'active_period_unit', 'fee_admin', 'fee_reseller', 'fee_partner'
    ];

    protected function casts(): array
    {
        return [
            'speed_mbps' => 'integer',
            'price' => 'integer',
            'installation_fee' => 'integer',
            'area_installation_fees' => 'array',
            'is_active' => 'boolean',
            'is_visible_to_sales' => 'boolean',
            'is_promo' => 'boolean',
            'promo_price' => 'integer',
            'is_mikrotik_group_custom' => 'boolean',
            'is_mikrotik_address_list_custom' => 'boolean',
            'shared_device' => 'integer',
            'active_period' => 'integer',
            'fee_admin' => 'integer',
            'fee_reseller' => 'integer',
            'fee_partner' => 'integer',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'package_id');
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /** Harga yang berlaku saat paket dipilih untuk pelanggan baru. */
    public function getCurrentPriceAttribute(): int
    {
        return (int) ($this->is_promo && $this->promo_price !== null
            ? $this->promo_price
            : $this->price);
    }

    public function getSpeedAttribute(): ?string
    {
        return $this->speed_mbps ? $this->speed_mbps . ' Mbps' : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Paket yang boleh dipilih dari Portal Sales / Afiliasi. */
    public function scopeVisibleToSales($query)
    {
        return $query->where('is_visible_to_sales', true);
    }

    /**
     * Cari konfigurasi biaya pasang khusus untuk area tertentu (berdasarkan ID atau Nama Area)
     */
    public function getAreaInstallationConfig($areaIdOrName): ?array
    {
        if (empty($this->area_installation_fees) || !is_array($this->area_installation_fees)) {
            return null;
        }

        $searchStr = strtolower(trim((string)$areaIdOrName));
        if ($searchStr === '') {
            return null;
        }

        foreach ($this->area_installation_fees as $key => $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['area_id']) && (string)$item['area_id'] === (string)$areaIdOrName) {
                return $item;
            }
            if (isset($item['area_name']) && strtolower(trim($item['area_name'])) === $searchStr) {
                return $item;
            }
            if (is_string($key) && strtolower(trim($key)) === $searchStr) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Nominal biaya pasang standar untuk area tertentu (fallback ke installation_fee umum)
     */
    public function getInstallationFeeForArea($areaIdOrName): float
    {
        $config = $this->getAreaInstallationConfig($areaIdOrName);
        if ($config && isset($config['fee']) && is_numeric($config['fee'])) {
            return (float)$config['fee'];
        }

        return (float)($this->installation_fee ?? 0);
    }

    /**
     * Opsi biaya pasang yang diizinkan untuk dipilih oleh Sales di area tertentu
     */
    public function getAllowedInstallationFeesForArea($areaIdOrName): array
    {
        $config = $this->getAreaInstallationConfig($areaIdOrName);
        $options = [];

        if ($config && !empty($config['allowed_options']) && is_array($config['allowed_options'])) {
            foreach ($config['allowed_options'] as $opt) {
                if (is_numeric($opt)) {
                    $options[] = (float)$opt;
                }
            }
        }

        // Sertakan biaya standar area
        $areaFee = $this->getInstallationFeeForArea($areaIdOrName);
        if (!in_array($areaFee, $options, false)) {
            $options[] = $areaFee;
        }

        sort($options);
        return array_values(array_unique($options));
    }
}
