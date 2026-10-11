<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherProfile extends Model
{
    protected $fillable = [
        'name',
        'price',
        'fee_admin',
        'fee_reseller',
        'fee_partner',
        'duration',
        'limit_rate',
        'shared_users',
        'is_customer_free',
        'description',
    ];

    protected $casts = [
        'is_customer_free' => 'boolean',
    ];

    protected $appends = [
        'reseller_buy_price',
        'reseller_margin',
    ];

    /**
     * Harga modal / beli reseller (Harga Jual - Reseller Margin).
     * Contoh: Harga Jual 5.000, Margin 1.000 -> Modal 4.000.
     */
    public function getResellerBuyPriceAttribute(): float
    {
        $sellPrice = (float) $this->price;
        $margin = (float) ($this->fee_reseller ?? 0);
        return max(0, $sellPrice - $margin);
    }

    /**
     * Margin / keuntungan reseller per voucher.
     */
    public function getResellerMarginAttribute(): float
    {
        return (float) ($this->fee_reseller ?? 0);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
}
