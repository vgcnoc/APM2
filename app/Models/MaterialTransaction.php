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
        'order_user_id',
        'giver_user_id',
        'order_account_name',
        'giver_account_name',
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
        'date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'is_migration',
    ];

    public function getIsMigrationAttribute(): bool
    {
        return str_contains($this->transaction_number ?? '', 'MIG')
            || str_contains(strtolower($this->purpose ?? ''), 'migrasi')
            || str_contains(strtolower($this->notes ?? ''), 'migrasi')
            || ($this->source_type ?? '') === 'migrasi';
    }

    public static bool $isCascadingCustomerDelete = false;
    public bool $skipStockRestoreOnDelete = false;

    protected static function booted(): void
    {
        static::deleting(function (MaterialTransaction $transaction) {
            if ($transaction->skipStockRestoreOnDelete) {
                MaterialTransactionItem::where('material_transaction_id', $transaction->id)->delete();
                return;
            }

            // Hanya kembalikan stok jika transaksi bertipe 'out', berstatus 'approved', dan bukan transaksi migrasi
            $shouldRestoreStock = ($transaction->type === 'out')
                && ($transaction->status === 'approved')
                && !$transaction->is_migration;

            // Ambil item langsung dari database agar item yang sudah dihapus manual tidak terproses dua kali karena cache relasi di memori
            $items = MaterialTransactionItem::with('material')
                ->where('material_transaction_id', $transaction->id)
                ->get();

            foreach ($items as $item) {
                if ($shouldRestoreStock && $item->material && (float) $item->quantity > 0) {
                    \App\Services\CustomerMaterialService::returnMaterialToWarehouse(
                        $item->material,
                        (float) $item->quantity,
                        $transaction->area_id,
                        $item->unit
                    );
                }
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

    public function orderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'order_user_id');
    }

    public function giverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'giver_user_id');
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
