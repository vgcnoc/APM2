<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternetPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'speed_mbps', 'price', 'access_mode', 'description', 'is_active',
        'color', 'is_promo', 'promo_price', 'is_mikrotik_group_custom', 'mikrotik_group',
        'is_mikrotik_address_list_custom', 'mikrotik_address_list', 'shared_device',
        'rate_limit', 'active_period', 'active_period_unit', 'fee_admin', 'fee_reseller', 'fee_partner'
    ];

    protected function casts(): array
    {
        return [
            'speed_mbps' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_promo' => 'boolean',
            'promo_price' => 'decimal:2',
            'is_mikrotik_group_custom' => 'boolean',
            'is_mikrotik_address_list_custom' => 'boolean',
            'shared_device' => 'integer',
            'active_period' => 'integer',
            'fee_admin' => 'decimal:2',
            'fee_reseller' => 'decimal:2',
            'fee_partner' => 'decimal:2',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
