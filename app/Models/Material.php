<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'name',
        'supplier',
        'category',
        'unit',
        'meter_per_roll',
        'total_rolls',
        'pcs_per_pack',
        'total_packs',
        'stock',
        'initial_stock',
        'price_per_unit',
        'selling_price',
        'description',
        'cm_per_pcs',
        'total_pieces',
        'is_active',
        'requires_sn',
        'requires_mac',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(MaterialStock::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(MaterialTransactionItem::class);
    }

    /**
     * Scope untuk tampilan di menu Data Material / Barang:
     * Menampilkan data hasil migrasi (ID <= 260 atau transaksi migrasi)
     * DAN material yang sudah selesai di-order via Order Toko (Pembelian Toko / Supplier).
     * Material yang baru sebatas katalog produk dan belum di-order tidak dimunculkan di Data Material.
     */
    public function scopeVisibleInMaterialsTable($query)
    {
        return $query->where(function ($q) {
            $q->where('id', '<=', 260)
              ->orWhereHas('transactionItems.transaction', function ($tq) {
                  $tq->where('transaction_number', 'like', '%MIG%')
                     ->orWhere('purpose', 'like', '%migrasi%')
                     ->orWhere('notes', 'like', '%migrasi%')
                     ->orWhere('source_type', 'migrasi')
                     ->orWhere('purpose', 'Pembelian Toko / Supplier');
              });
        });
    }

    protected static function booted(): void
    {
        static::saving(function (Material $material) {
            $material->recalcUnitTotals();
        });
    }

    /**
     * Sinkronkan total_rolls / total_packs / total_pieces berdasarkan stok (base unit).
     * Dipakai oleh Data Produk & Order Toko agar angka konversi selalu konsisten.
     */
    public function recalcUnitTotals(): self
    {
        $stock = (float) ($this->stock ?? 0);
        $category = strtolower((string) $this->category);

        if ($this->meter_per_roll > 0) {
            $this->total_rolls = $stock / $this->meter_per_roll;
        }

        $isIsolasi = str_contains(strtolower((string) $this->name), 'isolasi') || str_contains($category, 'isolasi');

        if ($isIsolasi) {
            $cmPerPcs = $this->cm_per_pcs > 0 ? $this->cm_per_pcs : 50;
            $pcsPerPack = $this->pcs_per_pack > 0 ? $this->pcs_per_pack : 1;
            $this->total_pieces = $stock / $cmPerPcs;
            $this->total_packs = $stock / ($cmPerPcs * $pcsPerPack);
        } elseif ($this->pcs_per_pack > 0) {
            $this->total_packs = $stock / $this->pcs_per_pack;
        }

        return $this;
    }
}
