<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FtthDesignDevice extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'meta' => 'array',
        ];
    }

    public function design()
    {
        return $this->belongsTo(FtthDesign::class, 'design_id');
    }
}
