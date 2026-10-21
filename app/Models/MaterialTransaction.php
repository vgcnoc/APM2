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
        'status',
        'date',
        'technician_name',
        'purpose',
        'source_type',
        'source_id',
        'source_reference',
        'handover_status',
        'request_type',
        'cabang',
        'area',
        'area_id',
        'notes',
        'total_cost',
        'user_id',
        'received_by',
        'received_at',
        'received_notes',
        'customer_id',
        'expense_id',
        'old_ont_id',
        'replacement_ont_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'date' => 'date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (MaterialTransaction $transaction) {
            foreach ($transaction->items as $item) {
                $item->delete();
            }
        });
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialTransactionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function areaModel(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function oldOnt(): BelongsTo
    {
        return $this->belongsTo(Ont::class, 'old_ont_id');
    }

    public function replacementOnt(): BelongsTo
    {
        return $this->belongsTo(Ont::class, 'replacement_ont_id');
    }
}
