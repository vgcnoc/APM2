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

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
}
