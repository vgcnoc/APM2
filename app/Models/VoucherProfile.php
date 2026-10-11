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
     * Harga beli / potong saldo reseller per voucher (Harga Jual).
     */
    public function getResellerBuyPriceAttribute(): float
    {
        return (float) $this->price;
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
