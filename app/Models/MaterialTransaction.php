<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'type',
        'date',
        'technician_name',
        'purpose',
        'cabang',
        'area',
        'area_id',
        'notes',
        'total_cost',
        'user_id',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MaterialTransactionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function areaModel(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
