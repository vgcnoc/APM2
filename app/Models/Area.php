<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['name', 'description', 'address', 'latitude', 'longitude', 'radius_meters'];

    public function stocks()
    {
        return $this->hasMany(MaterialStock::class);
    }

    public function routers()
    {
        return $this->hasMany(Router::class);
    }
}
