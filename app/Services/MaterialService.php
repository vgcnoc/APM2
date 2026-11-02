<?php

namespace App\Services;

use App\Models\CustomerInstalledMaterial;
use App\Models\CustomerMaterialDismantle;
use App\Models\FtthDesignMaterial;
use App\Models\InfrastructureWorkOrderMaterial;
use App\Models\InfrastructureWorkOrderMaterialRequest;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\NetworkMaintenanceMaterial;
use App\Models\PgjMaterial;
use App\Models\PgkMaterial;
use App\Models\PgkMaterialRequest;
use App\Models\PgsMaterial;
use App\Models\PgsMaterialRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaterialService
{
    /**
     * Cari material berdasarkan nama (case-insensitive & trimmed).
     */
    public static function findByName(string $name, ?int $excludeId = null): ?Material
    {
        $cleanName = strtolower(trim($name));
        if ($cleanName === '') {
            return null;
        }

        $query = Material::whereRaw('LOWER(TRIM(name)) = ?', [$cleanName]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Prioritaskan yang aktif, kemudian ID terkecil (canonical/awal)
        return $query->orderByDesc('is_active')->orderBy('id', 'asc')->first();
    }

    /**
     * Gabungkan / Timpa data material $source ke dalam material $target.
     * Semua stok area, mutasi transaksi, penggunaan teknisi, dan dismantle dialihkan ke $target.
     * Material $source kemudian dihapus agar tidak terjadi duplikasi.
     */
    public static function mergeMaterials(Material $target, Material $source, array $attributes = [], ?int $areaId = null, ?float $inputStock = null): Material
    {
        return DB::transaction(function () use ($target, $source, $attributes, $areaId, $inputStock) {
            // 1. Perbarui atribut target dengan data form terbaru
            $fillableAttributes = [
                'name', 'supplier', 'category', 'unit', 'meter_per_roll', 'total_rolls',
                'pcs_per_pack', 'total_packs', 'cm_per_pcs', 'total_pieces',
                'price_per_unit', 'selling_price', 'description', 'requires_sn', 'requires_mac',
            ];

            foreach ($fillableAttributes as $attr) {
                if (array_key_exists($attr, $attributes) && $attributes[$attr] !== null) {
                    $target->{$attr} = $attributes[$attr];
                }
            }

            if (!empty($attributes['name'])) {
                $target->name = trim($attributes['name']);
            }
            $target->is_active = true;

            // 2. Gabungkan stok area (material_stocks)
            $sourceStocks = MaterialStock::where('material_id', $source->id)->get();
            foreach ($sourceStocks as $sStock) {
                $targetStock = MaterialStock::where('material_id', $target->id)
                    ->where('area_id', $sStock->area_id)
                    ->first();

                if ($targetStock) {
                    $targetStock->stock = (float)$targetStock->stock + (float)$sStock->stock;
                    $targetStock->initial_stock = (float)$targetStock->initial_stock + (float)$sStock->initial_stock;
                    $targetStock->total_rolls = (float)$targetStock->total_rolls + (float)$sStock->total_rolls;
                    $targetStock->total_packs = (float)$targetStock->total_packs + (float)$sStock->total_packs;
                    $targetStock->total_pieces = (float)$targetStock->total_pieces + (float)$sStock->total_pieces;
                    $targetStock->save();
                    $sStock->delete();
                } else {
                    $sStock->material_id = $target->id;
                    $sStock->save();
                }
            }

            // Jika ada areaId spesifik yang sedang diedit dan target belum punya stok di area itu
            if ($areaId) {
                $targetAreaStock = MaterialStock::where('material_id', $target->id)
                    ->where('area_id', $areaId)
                    ->first();

                if (!$targetAreaStock) {
                    MaterialStock::create([
                        'material_id' => $target->id,
                        'area_id' => $areaId,
                        'stock' => $inputStock !== null ? $inputStock : 0,
                        'initial_stock' => $inputStock !== null ? $inputStock : 0,
                        'total_rolls' => $target->total_rolls ?? 0,
                        'total_packs' => $target->total_packs ?? 0,
                        'total_pieces' => $target->total_pieces ?? 0,
                    ]);
                }
            }

            // 3. Alihkan semua relasi foreign key dari $source->id ke $target->id
            MaterialTransactionItem::where('material_id', $source->id)->update(['material_id' => $target->id]);
            CustomerInstalledMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);
            CustomerMaterialDismantle::where('material_id', $source->id)->update(['material_id' => $target->id]);
            PgkMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);
            PgsMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);
            PgjMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);
            PgkMaterialRequest::where('material_id', $source->id)->update(['material_id' => $target->id]);
            PgsMaterialRequest::where('material_id', $source->id)->update(['material_id' => $target->id]);
            InfrastructureWorkOrderMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);
            InfrastructureWorkOrderMaterialRequest::where('material_id', $source->id)->update(['material_id' => $target->id]);
            NetworkMaintenanceMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);

            if (Schema::hasTable('ftth_design_materials')) {
                FtthDesignMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);
            }
            if (Schema::hasTable('material_request_items')) {
                DB::table('material_request_items')->where('material_id', $source->id)->update(['material_id' => $target->id]);
            }

            // 4. Bersihkan sisa stok source & hapus source material
            MaterialStock::where('material_id', $source->id)->delete();
            $source->delete();

            // 5. Hitung ulang total stok global & satuan konversi target
            $hasAreaStocks = MaterialStock::where('material_id', $target->id)->exists();
            if ($hasAreaStocks) {
                $target->stock = MaterialStock::where('material_id', $target->id)->sum('stock');
                $target->initial_stock = MaterialStock::where('material_id', $target->id)->sum('initial_stock');
            } elseif ($inputStock !== null) {
                $target->stock = (float)$target->stock + (float)$inputStock;
            }

            $target->recalcUnitTotals();
            $target->save();

            return $target;
        });
    }

    /**
     * Tambah stok atau mutasi ke material yang sudah ada (untuk mencegah duplikasi saat create/store).
     */
    public static function addStockToExistingMaterial(Material $target, array $validated, ?int $areaId = null): Material
    {
        return DB::transaction(function () use ($target, $validated, $areaId) {
            $inputStock = (float)($validated['stock'] ?? 0);
            $initialStock = (float)($validated['initial_stock'] ?? $inputStock);

            $target->is_active = true;

            // Update info konversi atau supplier jika belum terisi di target
            if (empty($target->supplier) && !empty($validated['supplier'])) {
                $target->supplier = $validated['supplier'];
            }
            if ((float)$target->meter_per_roll <= 0 && !empty($validated['meter_per_roll'])) {
                $target->meter_per_roll = $validated['meter_per_roll'];
            }
            if ((float)$target->pcs_per_pack <= 0 && !empty($validated['pcs_per_pack'])) {
                $target->pcs_per_pack = $validated['pcs_per_pack'];
            }
            if ((float)$target->cm_per_pcs <= 0 && !empty($validated['cm_per_pcs'])) {
                $target->cm_per_pcs = $validated['cm_per_pcs'];
            }

            if ($areaId) {
                $stockRecord = MaterialStock::where('material_id', $target->id)
                    ->where('area_id', $areaId)
                    ->first();

                if ($stockRecord) {
                    $stockRecord->stock = (float)$stockRecord->stock + $inputStock;
                    $stockRecord->initial_stock = (float)$stockRecord->initial_stock + $initialStock;
                    $stockRecord->total_rolls = (float)$stockRecord->total_rolls + (float)($validated['total_rolls'] ?? 0);
                    $stockRecord->total_packs = (float)$stockRecord->total_packs + (float)($validated['total_packs'] ?? 0);
                    $stockRecord->total_pieces = (float)$stockRecord->total_pieces + (float)($validated['total_pieces'] ?? 0);
                    $stockRecord->save();
                } else {
                    MaterialStock::create([
                        'material_id' => $target->id,
                        'area_id' => $areaId,
                        'stock' => $inputStock,
                        'initial_stock' => $initialStock,
                        'total_rolls' => $validated['total_rolls'] ?? 0,
                        'total_packs' => $validated['total_packs'] ?? 0,
                        'total_pieces' => $validated['total_pieces'] ?? 0,
                    ]);
                }

                if ($inputStock > 0) {
                    $transaction = MaterialTransaction::create([
                        'transaction_number' => 'IN-AREA-' . date('YmdHis') . '-' . $target->id,
                        'type' => 'in',
                        'status' => 'approved',
                        'date' => now(),
                        'technician_name' => auth()->user() ? auth()->user()->name : 'Admin',
                        'purpose' => 'Penambahan Stok Material Fisik Area',
                        'user_id' => auth()->id(),
                        'area_id' => $areaId,
                        'notes' => 'Penambahan stok material fisik yang sudah ada di sistem'
                    ]);

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $transaction->id,
                        'material_id' => $target->id,
                        'quantity' => $inputStock,
                        'unit' => $target->unit,
                        'price_per_unit' => 0,
                        'total_price' => 0,
                        'stock_before' => $stockRecord ? (float)$stockRecord->stock - $inputStock : 0,
                        'stock_after' => $stockRecord ? (float)$stockRecord->stock : $inputStock,
                        'condition' => 'Layak Pakai'
                    ]);
                }
            } else {
                $target->stock = (float)$target->stock + $inputStock;
                $target->initial_stock = (float)$target->initial_stock + $initialStock;
            }

            $hasAreaStocks = MaterialStock::where('material_id', $target->id)->exists();
            if ($hasAreaStocks) {
                $target->stock = MaterialStock::where('material_id', $target->id)->sum('stock');
                $target->initial_stock = MaterialStock::where('material_id', $target->id)->sum('initial_stock');
            }

            $target->recalcUnitTotals();
            $target->save();

            return $target;
        });
    }
}
