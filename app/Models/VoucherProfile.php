<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherProfile extends Model
{
    protected $fillable = [
        'name',
        'price',
        'duration',
        'limit_rate',
        'shared_users',
        'description',
    ];

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
}
