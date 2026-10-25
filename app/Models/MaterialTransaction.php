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

    public static bool $isCascadingCustomerDelete = false;

    protected static function booted(): void
    {
        static::deleting(function (MaterialTransaction $transaction) {
            // 1. Kembalikan stok untuk semua item transaksi ke gudang
            foreach ($transaction->items as $item) {
                if ($item->material && (float) $item->quantity > 0) {
                    \App\Services\CustomerMaterialService::returnMaterialToWarehouse(
                        $item->material,
                        (float) $item->quantity,
                        $transaction->area_id,
                        $item->unit
                    );
                }
                $item->delete();
            }

            // 2. Ketika transaksi material dihapus bukan edit, maka data pelanggan serta yang berhubungan ikut terhapus (Requirement 2)
            if ($transaction->customer_id && !static::$isCascadingCustomerDelete && !\App\Models\Customer::$isPurging) {
                $customer = \App\Models\Customer::find($transaction->customer_id);
                if ($customer) {
                    static::$isCascadingCustomerDelete = true;
                    try {
                        $customer->delete();
                    } finally {
                        static::$isCascadingCustomerDelete = false;
                    }
                }
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
