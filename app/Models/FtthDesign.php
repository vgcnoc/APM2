<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FtthDesign extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'total_distance' => 'decimal:2',
            'slack_percentage' => 'decimal:2',
        ];
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cableRoutes()
    {
        return $this->hasMany(FtthCableRoute::class, 'design_id');
    }

    public function designMaterials()
    {
        return $this->hasMany(FtthDesignMaterial::class, 'design_id');
    }

    public function designDevices()
    {
        return $this->hasMany(FtthDesignDevice::class, 'design_id');
    }

    // Computed
    public function getEstimatedCableAttribute()
    {
        $distance = $this->total_distance ?: $this->cableRoutes->sum('distance');
        $slack = $distance * ($this->slack_percentage / 100);
        return $distance + $slack;
    }
}
