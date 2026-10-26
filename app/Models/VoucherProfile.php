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
        'shared_users' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (VoucherProfile $profile) {
            if ($profile->is_customer_free || $profile->duration === 'Mengikuti Pelanggan') {
                $profile->shared_users = 1;
            }
        });
    }

    public function sessionLimit(): int
    {
        // Also enforce the single-session rule for legacy or directly imported free profiles.
        return $this->is_customer_free || $this->duration === 'Mengikuti Pelanggan'
            ? 1 : max(1, (int) $this->shared_users);
    }

    protected $appends = [
        'reseller_buy_price',
        'reseller_margin',
    ];

    /**
     * Harga modal / beli reseller (Harga Jual - Reseller Margin).
     * Contoh: Harga Jual Rp 3.000, Margin Rp 1.000 -> Modal Rp 2.000.
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
