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
        'is_migration',
        'is_catalog',
        'requires_sn',
        'requires_mac',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_migration' => 'boolean',
        'is_catalog' => 'boolean',
        'requires_sn' => 'boolean',
        'requires_mac' => 'boolean',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(MaterialStock::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(MaterialTransactionItem::class);
    }

    public function dismantles(): HasMany
    {
        return $this->hasMany(CustomerMaterialDismantle::class);
    }

    /**
     * Scope untuk tampilan di menu Katalog Produk & Satuan (Gudang Pusat):
     * Khusus produk/material murni untuk restock / order toko.
     * Material dari hasil migrasi maupun material operasional lokal/bekas TIDAK BOLEH muncul di sini.
     */
    public function scopeCatalogProducts($query)
    {
        return $query->where('is_catalog', true)
            ->where(function ($q) {
                $q->where('is_migration', false)->orWhereNull('is_migration');
            })
            ->where('id', '>', 260)
            ->where(function ($q) {
                $q->where('name', 'not like', '%(Bekas%')
                  ->where('name', 'not like', '%kabel bekas%');
            });
    }

    /**
     * Scope untuk tampilan di menu Data Material / Barang:
     * Menampilkan data hasil migrasi (is_migration = true atau ID <= 260 atau transaksi migrasi)
     * DAN material operasional fisik area (is_catalog = false atau pencatatan area)
     * DAN material yang sudah selesai di-order via Order Toko (Pembelian Toko / Supplier).
     * Material yang baru sebatas katalog produk dan belum di-order tidak dimunculkan di Data Material.
     */
    public function scopeVisibleInMaterialsTable($query)
    {
        return $query->where(function ($q) {
            $q->where('is_migration', true)
              ->orWhere('is_catalog', false)
              ->orWhere('id', '<=', 260)
              ->orWhereHas('transactionItems.transaction', function ($tq) {
                  $tq->where('transaction_number', 'like', '%MIG%')
                     ->orWhere('transaction_number', 'like', '%IN-AREA%')
                     ->orWhere('transaction_number', 'like', '%IN-INIT%')
                     ->orWhere('purpose', 'like', '%migrasi%')
                     ->orWhere('notes', 'like', '%migrasi%')
                     ->orWhere('source_type', 'migrasi')
                     ->orWhere('purpose', 'Pembelian Toko / Supplier')
                     ->orWhere('purpose', 'like', '%Material Fisik Area%')
                     ->orWhere('purpose', 'like', '%Material Area%')
                     ->orWhere('purpose', 'like', '%Stok Awal%');
              })
              ->orWhereHas('stocks', function ($sq) {
                  $sq->whereNotNull('area_id');
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
        $stock = max(0.0, (float) ($this->stock ?? 0));
        $this->stock = $stock;

        $category = strtolower((string) $this->category);
        $name = strtolower((string) $this->name);

        if ((float) $this->meter_per_roll > 0) {
            $this->total_rolls = $stock / (float) $this->meter_per_roll;
        }

        $isIsolasi = str_contains($name, 'isolasi')
            || str_contains($name, 'solasi')
            || str_contains($category, 'isolasi')
            || str_contains($category, 'solasi');

        if ($isIsolasi) {
            $cmPerPcs = (float) $this->cm_per_pcs > 0 ? (float) $this->cm_per_pcs : 50.0;
            $pcsPerPack = (float) $this->pcs_per_pack > 0 ? (float) $this->pcs_per_pack : 1.0;
            $this->total_pieces = $stock / $cmPerPcs;
            $this->total_packs = $stock / ($cmPerPcs * $pcsPerPack);
        } else {
            if ((float) $this->pcs_per_pack > 0) {
                $this->total_packs = $stock / (float) $this->pcs_per_pack;
            }
            if ((float) $this->meter_per_roll <= 0) {
                $this->total_pieces = $stock;
            }
        }

        return $this;
    }
}
