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
use App\Models\PgjMaterialRequest;
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
     * Deteksi apakah material termasuk kategori / jenis Isolasi (termasuk penulisan 'Solasi').
     */
    public static function isIsolasi(Material $material): bool
    {
        $cat = strtolower(trim((string) ($material->category ?? '')));
        $name = strtolower(trim((string) ($material->name ?? '')));

        return str_contains($cat, 'isolasi')
            || str_contains($cat, 'solasi')
            || str_contains($name, 'isolasi')
            || str_contains($name, 'solasi');
    }

    /**
     * Ambil atau buat baris MaterialStock untuk suatu area dengan row-level lock (anti race condition).
     */
    public static function getOrCreateLockedAreaStock(int $materialId, int $areaId): MaterialStock
    {
        $matStock = MaterialStock::where('material_id', $materialId)
            ->where('area_id', $areaId)
            ->lockForUpdate()
            ->first();

        if ($matStock) {
            return $matStock;
        }

        try {
            MaterialStock::create([
                'material_id' => $materialId,
                'area_id' => $areaId,
                'stock' => 0,
                'initial_stock' => 0,
                'total_rolls' => 0,
                'total_packs' => 0,
                'total_pieces' => 0,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Baris baru saja dibuat oleh request paralel; ambil ulang dengan lock di bawah
        }

        return MaterialStock::where('material_id', $materialId)
            ->where('area_id', $areaId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Generate nomor transaksi material unik & bebas bentrok meskipun ada penghapusan atau request bersamaan.
     */
    public static function generateTransactionNumber(string $prefix, ?string $ymd = null): string
    {
        $cleanPrefix = rtrim(strtoupper(trim($prefix)), '-');
        $datePart = $ymd ?: now()->format('Ymd');
        $likePattern = "{$cleanPrefix}-{$datePart}-%";

        $lastNumber = MaterialTransaction::where('transaction_number', 'like', $likePattern)
            ->orderByRaw('LENGTH(transaction_number) DESC')
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        $seq = 1;
        if ($lastNumber && preg_match('/-(\d+)$/', $lastNumber, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        } else {
            $seq = MaterialTransaction::whereDate('created_at', today())->count() + 1;
        }

        do {
            $candidate = "{$cleanPrefix}-{$datePart}-" . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            $exists = MaterialTransaction::where('transaction_number', $candidate)->exists();
            $seq++;
        } while ($exists);

        return $candidate;
    }

    /**
     * Gabungkan / Timpa data material $source ke dalam material $target.
     * Semua stok area, mutasi transaksi, penggunaan teknisi, dan dismantle dialihkan ke $target.
     * Material $source kemudian dihapus agar tidak terjadi duplikasi.
     */
    public static function mergeMaterials(Material $target, Material $source, array $attributes = [], ?int $areaId = null, ?float $inputStock = null): Material
    {
        return DB::transaction(function () use ($target, $source, $attributes, $areaId, $inputStock) {
            // Lock kedua material secara berurutan (ID kecil lebih dulu untuk mencegah deadlock)
            $firstId = min((int) $target->id, (int) $source->id);
            $secondId = max((int) $target->id, (int) $source->id);
            Material::whereKey($firstId)->lockForUpdate()->first();
            if ($secondId !== $firstId) {
                Material::whereKey($secondId)->lockForUpdate()->first();
            }
            $target->refresh();
            $source->refresh();

            // 1. Perbarui atribut target dengan data form terbaru
            $fillableAttributes = [
                'name', 'supplier', 'category', 'unit', 'meter_per_roll', 'total_rolls',
                'pcs_per_pack', 'total_packs', 'cm_per_pcs', 'total_pieces',
                'price_per_unit', 'selling_price', 'description', 'requires_sn', 'requires_mac',
                'is_catalog', 'is_migration',
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

            // 2. Gabungkan stok area (material_stocks) dengan lock
            $sourceStocks = MaterialStock::where('material_id', $source->id)->lockForUpdate()->get();
            foreach ($sourceStocks as $sStock) {
                $targetStock = MaterialStock::where('material_id', $target->id)
                    ->where('area_id', $sStock->area_id)
                    ->lockForUpdate()
                    ->first();

                if ($targetStock) {
                    $targetStock->stock = max(0.0, (float) $targetStock->stock + (float) $sStock->stock);
                    $targetStock->initial_stock = max(0.0, (float) $targetStock->initial_stock + (float) $sStock->initial_stock);
                    self::recalcAreaStockUnits($targetStock, $target)->save();
                    $sStock->delete();
                } else {
                    $sStock->material_id = $target->id;
                    self::recalcAreaStockUnits($sStock, $target)->save();
                }
            }

            // Jika ada areaId spesifik yang sedang diedit dan target belum punya stok di area itu
            if ($areaId) {
                $targetAreaStock = MaterialStock::where('material_id', $target->id)
                    ->where('area_id', $areaId)
                    ->lockForUpdate()
                    ->first();

                if (!$targetAreaStock) {
                    $createdAreaStock = self::getOrCreateLockedAreaStock($target->id, $areaId);
                    $createdAreaStock->stock = max(0.0, (float) ($inputStock ?? 0));
                    $createdAreaStock->initial_stock = max(0.0, (float) ($inputStock ?? 0));
                    self::recalcAreaStockUnits($createdAreaStock, $target)->save();
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
            if (Schema::hasTable('pgj_material_requests')) {
                PgjMaterialRequest::where('material_id', $source->id)->update(['material_id' => $target->id]);
            }
            InfrastructureWorkOrderMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);
            InfrastructureWorkOrderMaterialRequest::where('material_id', $source->id)->update(['material_id' => $target->id]);
            NetworkMaintenanceMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);

            if (Schema::hasTable('ftth_design_materials')) {
                FtthDesignMaterial::where('material_id', $source->id)->update(['material_id' => $target->id]);
            }
            if (Schema::hasTable('material_request_items')) {
                DB::table('material_request_items')->where('material_id', $source->id)->update(['material_id' => $target->id]);
            }

            // Update brand/material_id pada ONT dan nama di material_transaction_items
            \App\Models\Ont::where('brand', $source->name)->update(['brand' => $target->name]);
            if (Schema::hasColumn('onts', 'material_id')) {
                \App\Models\Ont::where('material_id', $source->id)->update(['material_id' => $target->id]);
            }
            MaterialTransactionItem::where('material_id', $target->id)->update(['material_name' => $target->name]);

            // 4. Bersihkan sisa stok source & hapus source material
            $sourceMasterStock = max(0.0, (float) ($source->stock ?? 0));
            $sourceMasterInitial = max(0.0, (float) ($source->initial_stock ?? 0));
            MaterialStock::where('material_id', $source->id)->delete();
            $source->delete();

            // 5. Hitung ulang total stok Gudang Pusat / Area & satuan konversi target
            if ($areaId) {
                self::syncMasterStockIfAreaOnly($target);
            } elseif ($inputStock !== null) {
                $target->stock = max(0.0, (float) $inputStock);
            } elseif ($sourceMasterStock > 0 && !self::shouldSyncMasterStockFromAreas($target)) {
                $target->stock = max(0.0, (float) $target->stock + $sourceMasterStock);
                $target->initial_stock = max(0.0, (float) $target->initial_stock + $sourceMasterInitial);
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
            $lockedTarget = Material::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $inputStock = max(0.0, (float) ($validated['stock'] ?? 0));
            $initialStock = max(0.0, (float) ($validated['initial_stock'] ?? $inputStock));

            $lockedTarget->is_active = true;

            // Pastikan jika ada flag is_catalog dan is_migration di validated, sinkronkan ke target
            if (isset($validated['is_catalog'])) {
                $lockedTarget->is_catalog = (bool) $validated['is_catalog'];
            }
            if (isset($validated['is_migration']) && $validated['is_migration'] === false) {
                $lockedTarget->is_migration = false;
            }

            // Update harga beli, harga jual, dan kategori jika disediakan di form
            if (isset($validated['price_per_unit']) && $validated['price_per_unit'] !== null && (float) $validated['price_per_unit'] > 0) {
                $lockedTarget->price_per_unit = $validated['price_per_unit'];
            }
            if (isset($validated['selling_price']) && $validated['selling_price'] !== null && (float) $validated['selling_price'] > 0) {
                $lockedTarget->selling_price = $validated['selling_price'];
            }
            if (!empty($validated['category']) && (empty($lockedTarget->category) || $lockedTarget->category === 'Umum')) {
                $lockedTarget->category = $validated['category'];
            }
            if (!empty($validated['unit']) && empty($lockedTarget->unit)) {
                $lockedTarget->unit = $validated['unit'];
            }
            if (!empty($validated['description']) && empty($lockedTarget->description)) {
                $lockedTarget->description = $validated['description'];
            }

            // Update info konversi atau supplier jika belum terisi di target
            if (empty($lockedTarget->supplier) && !empty($validated['supplier'])) {
                $lockedTarget->supplier = $validated['supplier'];
            }
            if ((float) $lockedTarget->meter_per_roll <= 0 && !empty($validated['meter_per_roll'])) {
                $lockedTarget->meter_per_roll = $validated['meter_per_roll'];
            }
            if ((float) $lockedTarget->pcs_per_pack <= 0 && !empty($validated['pcs_per_pack'])) {
                $lockedTarget->pcs_per_pack = $validated['pcs_per_pack'];
            }
            if ((float) $lockedTarget->cm_per_pcs <= 0 && !empty($validated['cm_per_pcs'])) {
                $lockedTarget->cm_per_pcs = $validated['cm_per_pcs'];
            }

            if ($areaId) {
                $stockRecord = self::getOrCreateLockedAreaStock($lockedTarget->id, $areaId);
                $stockBefore = (float) ($stockRecord->stock ?? 0);
                $stockRecord->stock = $stockBefore + $inputStock;
                $stockRecord->initial_stock = (float) ($stockRecord->initial_stock ?? 0) + $initialStock;
                self::recalcAreaStockUnits($stockRecord, $lockedTarget)->save();

                if ($inputStock > 0) {
                    $baseRate = self::calculateBaseUnitPrice($lockedTarget);
                    $transaction = MaterialTransaction::create([
                        'transaction_number' => self::generateTransactionNumber('IN-AREA'),
                        'type' => 'in',
                        'status' => 'approved',
                        'date' => now(),
                        'technician_name' => auth()->user() ? auth()->user()->name : 'Admin',
                        'purpose' => 'Penambahan Stok Material Fisik Area',
                        'user_id' => auth()->id(),
                        'area_id' => $areaId,
                        'total_cost' => $baseRate * $inputStock,
                        'notes' => 'Penambahan stok material fisik yang sudah ada di sistem'
                    ]);

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $transaction->id,
                        'material_id' => $lockedTarget->id,
                        'material_name' => $lockedTarget->name,
                        'quantity' => $inputStock,
                        'unit' => $lockedTarget->unit,
                        'price_per_unit' => $baseRate,
                        'total_price' => $baseRate * $inputStock,
                        'stock_before' => $stockBefore,
                        'stock_after' => (float) $stockRecord->stock,
                        'condition' => 'Layak Pakai'
                    ]);
                }

                self::syncMasterStockIfAreaOnly($lockedTarget);
            } else {
                $lockedTarget->stock = (float) $lockedTarget->stock + $inputStock;
                $lockedTarget->initial_stock = (float) $lockedTarget->initial_stock + $initialStock;
            }

            $lockedTarget->recalcUnitTotals();
            $lockedTarget->save();

            return $lockedTarget;
        });
    }

    /**
     * Konversi kuantitas input ke satuan dasar (base unit) material bila diinput dalam roll/pack/bungkus/pcs.
     */
    public static function convertToBaseQuantity(Material $material, float $quantity, ?string $unit = null): float
    {
        if ($quantity <= 0) {
            return 0.0;
        }

        $baseQty = (float) $quantity;
        $itemUnit = strtolower(trim((string) ($unit ?? $material->unit ?? '')));
        $matUnit = strtolower(trim((string) ($material->unit ?? '')));
        $catLower = strtolower((string) ($material->category ?? ''));
        $nameLower = strtolower((string) ($material->name ?? ''));

        if ($itemUnit === '' || $itemUnit === 'base' || $itemUnit === $matUnit) {
            return $baseQty;
        }

        // 1. Isolasi / Solasi (base unit umumnya 'cm')
        if (self::isIsolasi($material)) {
            $cmPerPcs = (float) $material->cm_per_pcs > 0 ? (float) $material->cm_per_pcs : 50.0;
            $pcsPerPack = (float) $material->pcs_per_pack > 0 ? (float) $material->pcs_per_pack : 1.0;

            if (in_array($itemUnit, ['pack', 'bungkus', 'bks'], true)) {
                if ($matUnit === 'pcs') {
                    return $quantity * $pcsPerPack;
                }
                return $quantity * ($cmPerPcs * $pcsPerPack);
            }

            if (in_array($itemUnit, ['pcs', 'pc', 'buah', 'biji', 'roll', 'rol'], true) && $matUnit === 'cm') {
                return $quantity * $cmPerPcs;
            }

            return $baseQty;
        }

        // 2. Kabel / Dropcore / Patchcord gulungan (roll -> meter)
        $hasRollConfig = (float) $material->meter_per_roll > 0
            || str_contains($catLower, 'kabel')
            || str_contains($nameLower, 'kabel')
            || str_contains($nameLower, 'dropcore');

        if ($hasRollConfig && in_array($itemUnit, ['roll', 'rol', 'haspel'], true) && $matUnit !== 'roll') {
            $meterPerRoll = (float) $material->meter_per_roll > 0 ? (float) $material->meter_per_roll : 1000.0;
            return $quantity * $meterPerRoll;
        }

        // 3. Klem / Paku Klem / Aksesoris ber-pack (pack/bungkus -> pcs)
        $hasPackConfig = (float) $material->pcs_per_pack > 0
            || in_array($catLower, ['klem', 'paku klem'], true)
            || str_contains($catLower, 'klem')
            || str_contains($nameLower, 'klem');

        if ($hasPackConfig && in_array($itemUnit, ['pack', 'bungkus', 'bks'], true) && !in_array($matUnit, ['pack', 'bungkus'], true)) {
            $pcsPerPack = (float) $material->pcs_per_pack > 0 ? (float) $material->pcs_per_pack : 1.0;
            return $quantity * $pcsPerPack;
        }

        // 4. Material umum dengan cm_per_pcs (pcs -> cm)
        if ((float) $material->cm_per_pcs > 0 && in_array($itemUnit, ['pcs', 'pc', 'buah'], true) && $matUnit === 'cm') {
            return $quantity * (float) $material->cm_per_pcs;
        }

        return $baseQty;
    }

    /**
     * Hitung harga satuan dasar (per 1 base unit: meter / cm / pcs) secara konsisten sesuai konfigurasi master produk.
     */
    public static function calculateBaseUnitPrice(Material $material, bool $preferSellingPrice = false): float
    {
        $rawMasterPrice = $preferSellingPrice
            ? (float) ($material->selling_price ?: ($material->price_per_unit ?? 0))
            : (float) ($material->price_per_unit ?? 0);

        if ($rawMasterPrice <= 0) {
            return 0.0;
        }

        $matUnit = strtolower(trim((string) ($material->unit ?? 'pcs')));

        // 1. Isolasi: di Katalog Produk (unit = 'cm'), price_per_unit sudah disimpan per cm
        if (self::isIsolasi($material)) {
            $cmPerPcs = (float) $material->cm_per_pcs > 0 ? (float) $material->cm_per_pcs : 50.0;
            $pcsPerPack = (float) $material->pcs_per_pack > 0 ? (float) $material->pcs_per_pack : 1.0;

            if ($matUnit === 'cm') {
                return $rawMasterPrice;
            }
            if (in_array($matUnit, ['pack', 'bungkus', 'bks'], true)) {
                return $rawMasterPrice;
            }
            // Bila unit disimpan 'pcs', maka harga master adalah per pcs
            return $rawMasterPrice;
        }

        // 2. Kabel / Patchcord dengan meter_per_roll > 0: harga master adalah per Roll, base unit 'meter'
        if ((float) $material->meter_per_roll > 0 && !in_array($matUnit, ['roll', 'rol', 'haspel'], true)) {
            return $rawMasterPrice / (float) $material->meter_per_roll;
        }

        // 3. Klem / Aksesoris ber-pack dengan pcs_per_pack > 0: harga master adalah per Bungkus/Pack, base unit 'pcs'
        if ((float) $material->pcs_per_pack > 0 && !in_array($matUnit, ['pack', 'bungkus', 'bks'], true)) {
            return $rawMasterPrice / (float) $material->pcs_per_pack;
        }

        return $rawMasterPrice;
    }

    /**
     * Hitung harga per 1 satuan spesifik ($unit: 'roll', 'pack', 'bungkus', 'pcs', 'meter', 'cm', atau 'base').
     */
    public static function calculateUnitPrice(Material $material, ?string $unit = null, bool $preferSellingPrice = false): float
    {
        $targetUnit = strtolower(trim((string) ($unit ?? $material->unit ?? 'base')));
        $matUnit = strtolower(trim((string) ($material->unit ?? 'pcs')));
        $basePrice = self::calculateBaseUnitPrice($material, $preferSellingPrice);

        if ($basePrice <= 0) {
            return 0.0;
        }

        if ($targetUnit === '' || $targetUnit === 'base' || $targetUnit === $matUnit) {
            return $basePrice;
        }

        if (self::isIsolasi($material)) {
            $cmPerPcs = (float) $material->cm_per_pcs > 0 ? (float) $material->cm_per_pcs : 50.0;
            $pcsPerPack = (float) $material->pcs_per_pack > 0 ? (float) $material->pcs_per_pack : 1.0;
            // Konversi ke rate per cm dulu bila matUnit bukan cm
            $perCm = $matUnit === 'cm'
                ? $basePrice
                : (in_array($matUnit, ['pack', 'bungkus', 'bks'], true)
                    ? ($basePrice / ($cmPerPcs * $pcsPerPack))
                    : ($basePrice / $cmPerPcs));

            if (in_array($targetUnit, ['pack', 'bungkus', 'bks'], true)) {
                return $perCm * ($cmPerPcs * $pcsPerPack);
            }
            if (in_array($targetUnit, ['pcs', 'pc', 'buah', 'biji', 'roll', 'rol'], true)) {
                return $perCm * $cmPerPcs;
            }
            if ($targetUnit === 'cm') {
                return $perCm;
            }
        }

        $factor = self::convertToBaseQuantity($material, 1.0, $targetUnit);
        return $basePrice * ($factor > 0 ? $factor : 1.0);
    }

    /**
     * Hitung konversi kuantitas & harga total untuk Order Toko / Transaksi Material secara terpusat.
     *
     * @return array{converted_qty: float, base_unit: string, unit_price: float, base_unit_price: float, total_price: float}
     */
    public static function calculateOrderPricing(
        Material $material,
        float $quantity,
        ?string $purchaseUnit = null,
        ?float $overrideUnitPrice = null,
        bool $preferSellingPrice = false
    ): array {
        $qty = max(0.0, (float) $quantity);
        $pUnit = strtolower(trim((string) ($purchaseUnit ?? 'base')));

        // Pastikan Isolasi katalog selalu ber-unit dasar 'cm' jika memiliki cm_per_pcs
        $baseUnit = $material->unit ?: 'pcs';
        if (self::isIsolasi($material) && (float) $material->cm_per_pcs > 0 && strtolower((string) $material->unit) !== 'cm' && $material->is_catalog) {
            $material->unit = 'cm';
            $baseUnit = 'cm';
        }

        $convertedQty = self::convertToBaseQuantity($material, $qty, $pUnit);
        $unitPrice = ($overrideUnitPrice !== null && $overrideUnitPrice >= 0)
            ? (float) $overrideUnitPrice
            : self::calculateUnitPrice($material, $pUnit, $preferSellingPrice);

        $totalPrice = $qty * $unitPrice;
        $baseUnitPrice = $convertedQty > 0 ? ($totalPrice / $convertedQty) : self::calculateBaseUnitPrice($material, $preferSellingPrice);

        return [
            'converted_qty' => $convertedQty,
            'base_unit' => $baseUnit,
            'unit_price' => $unitPrice,
            'base_unit_price' => $baseUnitPrice,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Hitung ulang konversi total_rolls, total_packs, dan total_pieces pada MaterialStock area.
     */
    public static function recalcAreaStockUnits(MaterialStock $matStock, Material $material): MaterialStock
    {
        $stock = max(0.0, (float) ($matStock->stock ?? 0));
        $matStock->stock = $stock;

        $isIsolasi = self::isIsolasi($material);

        if ((float) $material->meter_per_roll > 0) {
            $matStock->total_rolls = $stock / (float) $material->meter_per_roll;
        }

        if ($isIsolasi) {
            $cmPerPcs = (float) $material->cm_per_pcs > 0 ? (float) $material->cm_per_pcs : 50.0;
            $pcsPerPack = (float) $material->pcs_per_pack > 0 ? (float) $material->pcs_per_pack : 1.0;
            $matStock->total_pieces = $stock / $cmPerPcs;
            $matStock->total_packs = $stock / ($cmPerPcs * $pcsPerPack);
        } else {
            if ((float) $material->pcs_per_pack > 0) {
                $matStock->total_packs = $stock / (float) $material->pcs_per_pack;
            }
            if ((float) $material->meter_per_roll <= 0) {
                $matStock->total_pieces = $stock;
            }
        }

        return $matStock;
    }

    /**
     * Tentukan apakah materials.stock (master) boleh disinkronkan dari sum(material_stocks.stock).
     * Produk katalog Gudang Pusat / Order Toko TIDAK disinkronkan agar stok Gudang Pusat tetap terpisah dari stok cabang.
     */
    public static function shouldSyncMasterStockFromAreas(Material $material): bool
    {
        if ($material->is_catalog && !$material->is_migration && (int) $material->id > 260) {
            return false;
        }

        if ($material->is_migration || (int) $material->id <= 260) {
            return true;
        }

        $hasOrderToko = MaterialTransactionItem::where('material_id', $material->id)
            ->whereHas('transaction', function ($q) {
                $q->where('purpose', 'Pembelian Toko / Supplier');
            })
            ->exists();

        if ($hasOrderToko) {
            return false;
        }

        return MaterialTransactionItem::where('material_id', $material->id)
            ->whereHas('transaction', function ($q) {
                $q->where('transaction_number', 'like', 'IN-AREA-%')
                  ->orWhere('purpose', 'like', '%Material Fisik Area%');
            })
            ->exists();
    }

    /**
     * Sinkronkan materials.stock dengan total stok seluruh area khusus untuk material fisik area / migrasi.
     */
    public static function syncMasterStockIfAreaOnly(Material $material): void
    {
        if (self::shouldSyncMasterStockFromAreas($material)) {
            $totalAreaStock = (float) MaterialStock::where('material_id', $material->id)->sum('stock');
            $material->stock = max(0.0, $totalAreaStock);
            $material->recalcUnitTotals()->save();
        }
    }

    /**
     * Potong stok operasional secara atomik & konsisten (prioritas Gudang Area jika areaId tersedia).
     *
     * @return array{stock_before: float, stock_after: float, base_qty: float}
     */
    public static function deductOperationalStock(
        Material $material,
        float $quantity,
        ?int $areaId = null,
        ?string $unit = null
    ): array {
        $baseQty = self::convertToBaseQuantity($material, $quantity, $unit);
        if ($baseQty <= 0) {
            $cur = $areaId
                ? (float) (MaterialStock::where('material_id', $material->id)->where('area_id', $areaId)->value('stock') ?? 0)
                : (float) ($material->stock ?? 0);
            return ['stock_before' => $cur, 'stock_after' => $cur, 'base_qty' => 0.0];
        }

        return DB::transaction(function () use ($material, $baseQty, $areaId) {
            $lockedMat = Material::whereKey($material->id)->lockForUpdate()->first() ?? $material;

            if ($areaId) {
                $matStock = self::getOrCreateLockedAreaStock($lockedMat->id, $areaId);
                $stockBefore = (float) ($matStock->stock ?? 0);
                $stockAfter = max(0.0, $stockBefore - $baseQty);
                $matStock->stock = $stockAfter;
                self::recalcAreaStockUnits($matStock, $lockedMat)->save();
                self::syncMasterStockIfAreaOnly($lockedMat);
                $material->stock = $lockedMat->stock;

                return [
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'base_qty' => $baseQty,
                ];
            }

            $stockBefore = (float) ($lockedMat->stock ?? 0);
            $stockAfter = max(0.0, $stockBefore - $baseQty);
            $lockedMat->stock = $stockAfter;
            $lockedMat->recalcUnitTotals()->save();
            $material->stock = $lockedMat->stock;

            return [
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'base_qty' => $baseQty,
            ];
        });
    }

    /**
     * Kembalikan / tambah stok operasional secara atomik & konsisten (prioritas Gudang Area jika areaId tersedia).
     *
     * @return array{stock_before: float, stock_after: float, base_qty: float}
     */
    public static function restoreOperationalStock(
        Material $material,
        float $quantity,
        ?int $areaId = null,
        ?string $unit = null
    ): array {
        $baseQty = self::convertToBaseQuantity($material, $quantity, $unit);
        if ($baseQty <= 0) {
            $cur = $areaId
                ? (float) (MaterialStock::where('material_id', $material->id)->where('area_id', $areaId)->value('stock') ?? 0)
                : (float) ($material->stock ?? 0);
            return ['stock_before' => $cur, 'stock_after' => $cur, 'base_qty' => 0.0];
        }

        return DB::transaction(function () use ($material, $baseQty, $areaId) {
            $lockedMat = Material::whereKey($material->id)->lockForUpdate()->first() ?? $material;

            if ($areaId) {
                $matStock = self::getOrCreateLockedAreaStock($lockedMat->id, $areaId);
                $stockBefore = (float) ($matStock->stock ?? 0);
                $stockAfter = max(0.0, $stockBefore + $baseQty);
                $matStock->stock = $stockAfter;
                self::recalcAreaStockUnits($matStock, $lockedMat)->save();
                self::syncMasterStockIfAreaOnly($lockedMat);
                $material->stock = $lockedMat->stock;

                return [
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'base_qty' => $baseQty,
                ];
            }

            $stockBefore = (float) ($lockedMat->stock ?? 0);
            $stockAfter = max(0.0, $stockBefore + $baseQty);
            $lockedMat->stock = $stockAfter;
            $lockedMat->recalcUnitTotals()->save();
            $material->stock = $lockedMat->stock;

            return [
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'base_qty' => $baseQty,
            ];
        });
    }

    /**
     * Resolusi model Material yang terkait dengan suatu unit perangkat ONT.
     */
    public static function resolveOntMaterial(\App\Models\Ont $ont): ?Material
    {
        if (!empty($ont->material_transaction_item_id)) {
            $mItem = MaterialTransactionItem::with('material')->find($ont->material_transaction_item_id);
            if ($mItem && $mItem->material) {
                return $mItem->material;
            }
        }

        $brand = trim((string) ($ont->brand ?? ''));
        $model = trim((string) ($ont->model ?? ''));

        if ($brand === '' && $model === '') {
            return null;
        }

        $baseQuery = Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->where(function ($q) {
            $q->where('category', 'ONT')
              ->orWhere('category', 'like', '%ont%')
              ->orWhere('category', 'like', '%modem%');
        });

        // 1. Cek model spesifik F660
        $combinedText = strtolower($brand . ' ' . $model);
        if (str_contains($combinedText, '660')) {
            $f660Mat = (clone $baseQuery)->where('name', 'like', '%660%')
                ->orderByRaw('CASE WHEN id > 260 THEN 0 ELSE 1 END')
                ->first();
            if ($f660Mat) {
                return $f660Mat;
            }
        }

        if ($brand !== '' && $model !== '' && stripos($brand, $model) === false) {
            $exact = (clone $baseQuery)
                ->where('name', 'like', "%{$brand}%")
                ->where('name', 'like', "%{$model}%")
                ->orderByRaw('CASE WHEN id > 260 THEN 0 ELSE 1 END')
                ->first();
            if ($exact) {
                return $exact;
            }
        }

        if ($brand !== '') {
            $byBrand = (clone $baseQuery)
                ->where(function ($q) use ($brand) {
                    $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($brand)])
                      ->orWhere('name', 'like', "%{$brand}%");
                })
                ->orderByRaw('CASE WHEN LOWER(TRIM(name)) = ? THEN 0 ELSE 1 END', [strtolower($brand)])
                ->orderByRaw('CASE WHEN id > 260 THEN 0 ELSE 1 END')
                ->first();
            if ($byBrand) {
                return $byBrand;
            }
        }

        return null;
    }
}

