<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    /** @use HasFactory<\Database\Factories\ResellerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'area_id',
        'latitude',
        'longitude',
        'ktp_photo',
        'balance',
        'is_active',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
