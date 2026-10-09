<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialTransactionItem extends Model
{
    protected $fillable = [
        'material_transaction_id',
        'material_id',
        'quantity',
        'unit',
        'price_per_unit',
        'total_price',
        'is_registered_to_ont',
        'stock_before',
        'stock_after',
        'condition',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MaterialTransaction::class, 'material_transaction_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
