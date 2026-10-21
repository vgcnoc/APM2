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

    protected static function booted(): void
    {
        static::deleting(function (MaterialTransactionItem $item) {
            $onts = Ont::where('material_transaction_item_id', $item->id)->get();
            foreach ($onts as $ont) {
                if (!$ont->customer_id && !$ont->odp_id) {
                    $ont->delete();
                } else {
                    $ont->update(['material_transaction_item_id' => null]);
                }
            }
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MaterialTransaction::class, 'material_transaction_id');
    }

    public function materialTransaction(): BelongsTo
    {
        return $this->belongsTo(MaterialTransaction::class, 'material_transaction_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function onts()
    {
        return $this->hasMany(Ont::class, 'material_transaction_item_id');
    }
}
