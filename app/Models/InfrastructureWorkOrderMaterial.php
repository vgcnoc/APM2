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
        'material_transaction_item_id',
        'trx_number',
        'requested_qty',
        'used_qty',
        'returned_qty',
        'unit',
        'status',
        'notes',
    ];

    public function materialTransactionItem(): BelongsTo
    {
        return $this->belongsTo(MaterialTransactionItem::class, 'material_transaction_item_id');
    }

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
