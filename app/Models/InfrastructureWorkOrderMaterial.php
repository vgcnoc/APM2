<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfrastructureWorkOrderMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'material_id',
        'requested_qty',
        'used_qty',
        'returned_qty',
        'unit',
        'status',
    ];

    protected $casts = [
        'requested_qty' => 'decimal:2',
        'used_qty' => 'decimal:2',
        'returned_qty' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(InfrastructureWorkOrder::class, 'work_order_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
