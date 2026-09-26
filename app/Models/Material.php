<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'name',
        'category',
        'unit',
        'stock',
        'price_per_unit',
        'description',
    ];
}
