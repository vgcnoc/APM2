<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OdpPort extends Model
{
    use HasFactory;

    protected $fillable = [
        'odp_id',
        'port_number',
        'status',
        'description',
    ];

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function ont(): HasOne
    {
        return $this->hasOne(Ont::class, 'odp_port_id');
    }
}
