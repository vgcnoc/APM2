<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FtthDesignMaterial extends Model
{
    protected $guarded = ['id'];

    public function design()
    {
        return $this->belongsTo(FtthDesign::class, 'design_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
