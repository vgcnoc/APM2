<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'voucher_profile_id',
        'code',
        'username',
        'password',
        'status',
        'is_active',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function profile()
    {
        return $this->belongsTo(VoucherProfile::class, 'voucher_profile_id');
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }
}
