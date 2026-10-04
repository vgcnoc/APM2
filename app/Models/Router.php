<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    protected $fillable = [
        'name',
        'nas_id',
        'api_port',
        'winbox_port',
        'pic_id',
    ];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function nas()
    {
        return $this->belongsTo(\App\Models\Radius\Nas::class, 'nas_id');
    }
}
