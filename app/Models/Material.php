<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'name',
        'supplier',
        'category',
        'unit',
        'meter_per_roll',
        'total_rolls',
        'stock',
        'price_per_unit',
        'selling_price',
        'description',
    ];
}
