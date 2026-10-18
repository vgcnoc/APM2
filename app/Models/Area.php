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

    public function customers()
    {
        return $this->hasMany(Customer::class, 'area_id');
    }

    public function odps()
    {
        return $this->hasMany(Odp::class, 'area_id');
    }

    public function odcs()
    {
        return $this->hasMany(Odc::class, 'area_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'area_id');
    }
}
