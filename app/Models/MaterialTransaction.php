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

            $isStorePurchase = ($transaction->purpose === 'Pembelian Toko / Supplier')
                || str_starts_with((string) $transaction->transaction_number, 'PO-');

            // Ambil item langsung dari database agar item yang sudah dihapus manual tidak terproses dua kali karena cache relasi di memori
            $items = MaterialTransactionItem::with('material')
                ->where('material_transaction_id', $transaction->id)
                ->get();

            if ($transaction->status === 'approved' && !$transaction->is_migration) {
                foreach ($items as $item) {
                    $material = $item->material;
                    $qty = (float) $item->quantity;
                    if (!$material || $qty <= 0) {
                        continue;
                    }

                    if ($isStorePurchase) {
                        // Order Toko memindahkan stok dari Gudang Pusat (materials.stock) ke Gudang Cabang Area (material_stocks.stock)
                        $baseQty = \App\Services\MaterialService::convertToBaseQuantity($material, $qty, $item->unit);
                        if ($transaction->area_id) {
                            $matStock = MaterialStock::where('material_id', $material->id)
                                ->where('area_id', $transaction->area_id)
                                ->first();
                            if ($matStock) {
                                $matStock->stock = max(0, (float) $matStock->stock - $baseQty);
                                \App\Services\MaterialService::recalcAreaStockUnits($matStock, $material);
                                $matStock->save();
                            }
                        }
                        $material->stock = (float) $material->stock + $baseQty;
                        $material->recalcUnitTotals()->save();
                    } elseif ($transaction->type === 'out') {
                        \App\Services\MaterialService::restoreOperationalStock(
                            $material,
                            $qty,
                            $transaction->area_id,
                            $item->unit
                        );
                    } elseif ($transaction->type === 'in') {
                        $cond = trim((string) ($item->condition ?? 'Layak Pakai'));
                        if ($cond === 'Layak Pakai') {
                            \App\Services\MaterialService::deductOperationalStock(
                                $material,
                                $qty,
                                $transaction->area_id,
                                $item->unit
                            );
                        }
                    }
                }
            }

            foreach ($items as $item) {
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
