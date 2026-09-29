<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OltPon extends Model
{
    use HasFactory;

    protected $fillable = [
        'olt_id',
        'port_number',
        'name',
        'capacity',
        'status',
        'description',
    ];

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function odcs(): HasMany
    {
        return $this->hasMany(Odc::class, 'pon_id');
    }

    public function getUsedCapacityAttribute()
    {
        return $this->odcs->reduce(function ($carry, $odc) {
            return $carry + $odc->odps->sum('used_ports');
        }, 0);
    }
}
