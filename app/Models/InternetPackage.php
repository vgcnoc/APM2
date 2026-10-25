<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternetPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'speed_mbps', 'price', 'installation_fee', 'access_mode', 'description', 'is_active', 'is_visible_to_sales',
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
}
