<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfrastructureWorkOrderOpticalCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'port_number',
        'wavelength',
        'rx_power_dbm',
        'status',
        'notes',
    ];

    protected $casts = [
        'port_number' => 'integer',
        'rx_power_dbm' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(InfrastructureWorkOrder::class, 'work_order_id');
    }
}
