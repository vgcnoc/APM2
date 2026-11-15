<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Router extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'area_id',
        'nas_id',
        'api_port',
        'winbox_port',
        'pic_id',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function nas()
    {
        return $this->belongsTo(\App\Models\Radius\Nas::class, 'nas_id');
    }
}
