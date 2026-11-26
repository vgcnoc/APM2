<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\Area;
use App\Services\MaterialService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $areas = Area::orderBy('id')->get();
            $headquarterArea = Area::where('name', 'like', '%BC 1%')->first()
                ?: Area::where('name', 'like', '%Sirnajaya%')->first()
                ?: $areas->first();

            $hqAreaId = $headquarterArea ? $headquarterArea->id : 2;
            $allAreaIds = $areas->pluck('id')->all();

            // 1. Alokasikan stok untuk 10 material yang memiliki master stock tapi belum memiliki rekaman di material_stocks
            $unallocatedIds = [263, 264, 266, 267, 332, 351, 355, 357, 361, 365];
            foreach ($unallocatedIds as $matId) {
                $material = Material::find($matId);
                if (!$material) continue;

                $stockVal = max(0.0, (float) ($material->stock ?? 0));
                if ($stockVal > 0) {
                    $hqStock = MaterialStock::firstOrNew([
                        'material_id' => $material->id,
                        'area_id' => $hqAreaId,
                    ]);
                    $hqStock->stock = $stockVal;
                    $hqStock->initial_stock = max((float) ($hqStock->initial_stock ?? 0), $stockVal);
                    MaterialService::recalcAreaStockUnits($hqStock, $material)->save();
                }

                // Buat rekaman 0 untuk area lain agar terdaftar lengkap di semua cabang
                foreach ($allAreaIds as $areaId) {
                    if ($areaId === $hqAreaId) continue;
                    $otherStock = MaterialStock::firstOrNew([
                        'material_id' => $material->id,
                        'area_id' => $areaId,
                    ]);
                    if (!$otherStock->exists) {
                        $otherStock->stock = 0;
                        $otherStock->initial_stock = 0;
                        MaterialService::recalcAreaStockUnits($otherStock, $material)->save();
                    }
                }
            }

            // 2. Pastikan SELURUH material aktif memiliki baris di material_stocks untuk setiap area
            $activeMaterials = Material::where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })->get();

            foreach ($activeMaterials as $m) {
                foreach ($allAreaIds as $areaId) {
                    $stock = MaterialStock::firstOrNew([
                        'material_id' => $m->id,
                        'area_id' => $areaId,
                    ]);
                    if (!$stock->exists) {
                        $stock->stock = 0;
                        $stock->initial_stock = 0;
                        MaterialService::recalcAreaStockUnits($stock, $m)->save();
                    } else {
                        MaterialService::recalcAreaStockUnits($stock, $m)->save();
                    }
                }

                // 3. Rekonsiliasi master stock = sum(material_stocks.stock)
                $sumArea = (float) MaterialStock::where('material_id', $m->id)->sum('stock');
                $m->stock = max(0.0, $sumArea);
                $m->recalcUnitTotals()->save();
            }

            // 4. Arsipkan material sisa migrasi / batch historis yang sudah habis (stok = 0)
            // agar tidak mengotori tabel inventaris aktif gudang
            $archivePatterns = [
                '(migrasi',
                '(bekas layak pakai)',
                '(batch',
                '-Nay',
                '-Yud',
                '-A12',
                '-By',
                '-SNW',
            ];

            $zeroStockObsolete = Material::where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })->where(function ($q) use ($archivePatterns) {
                $q->where('is_migration', true)
                  ->orWhere('id', '<=', 260);
                foreach ($archivePatterns as $pat) {
                    $q->orWhere('name', 'like', "%{$pat}%");
                }
            })->get();

            foreach ($zeroStockObsolete as $zm) {
                $totalStock = (float) MaterialStock::where('material_id', $zm->id)->sum('stock');
                // HANYA nonaktifkan jika benar-benar bernilai 0 di semua area
                if ($totalStock <= 0.0001 && (float) $zm->stock <= 0.0001) {
                    $zm->update([
                        'is_active' => false,
                        'is_migration' => true,
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback
    }
};

