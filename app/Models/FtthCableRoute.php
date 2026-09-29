<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FtthCableRoute extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'route_points' => 'array',
            'distance' => 'decimal:2',
        ];
    }

    public function design()
    {
        return $this->belongsTo(FtthDesign::class, 'design_id');
    }
}
