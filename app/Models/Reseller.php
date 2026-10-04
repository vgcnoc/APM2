<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    /** @use HasFactory<\Database\Factories\ResellerFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'balance',
        'is_active',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
