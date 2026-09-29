<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialStock extends Model
{
    protected $fillable = [
        'material_id',
        'area_id',
        'stock',
        'initial_stock',
        'total_rolls',
        'total_packs',
        'total_pieces',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
