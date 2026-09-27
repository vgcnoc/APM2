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
        'pcs_per_pack',
        'total_packs',
        'stock',
        'initial_stock',
        'price_per_unit',
        'selling_price',
        'description',
        'cm_per_pcs',
        'total_pieces',
    ];
}
