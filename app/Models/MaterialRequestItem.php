<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequestItem extends Model
{
    protected $fillable = [
        'material_request_id',
        'material_id',
        'quantity',
        'unit',
    ];

    public function request()
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
