<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerBalanceRequest extends Model
{
    protected $fillable = [
        'reseller_id',
        'amount',
        'payment_method',
        'status',
        'notes',
        'approved_by'
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
