<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerInstalledMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'material_id',
        'item_name',
        'quantity',
        'unit',
        'serial_number',
        'installed_at',
        'installation_source',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'installed_at' => 'datetime',
    ];

    public static bool $isCascadingDelete = false;

    protected static function booted(): void
    {
        static::deleting(function (CustomerInstalledMaterial $item) {
            // 1. Kembalikan stok material ke gudang
            if ($item->material_id && (float) $item->quantity > 0) {
                $areaId = $item->customer?->area_id;
                \App\Services\CustomerMaterialService::returnMaterialToWarehouse(
                    (int) $item->material_id,
                    (float) $item->quantity,
                    $areaId,
                    $item->unit
                );
            }

            // 2. Hapus riwayat bongkaran terkait
            try {
                $item->dismantles()->delete();
            } catch (\Throwable $e) {}

            // 3. Ketika material dihapus bukan edit, maka data pelanggan serta yang berhubungan ikut terhapus (Requirement 2)
            if ($item->customer_id && !static::$isCascadingDelete && !\App\Models\Customer::$isPurging) {
                $customer = \App\Models\Customer::find($item->customer_id);
                if ($customer) {
                    static::$isCascadingDelete = true;
                    try {
                        $customer->delete();
                    } finally {
                        static::$isCascadingDelete = false;
                    }
                }
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function dismantles(): HasMany
    {
        return $this->hasMany(CustomerMaterialDismantle::class, 'customer_installed_material_id');
    }
}
