<?php

use App\Models\Material;
use App\Services\MaterialService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Mappings eksplisit untuk material dengan penamaan tidak standar / personil tags yang ditemukan
        $explicitMerges = [
            330 => 279, // 'Kabel Frecon 100m (migrasi)' -> 'Frecon 100m'
            320 => 279, // 'Kabel Frecon 100m-NAY' -> 'Frecon 100m'
            318 => 276, // 'Kabel Frecon 150m-A12' -> 'Frecon 150m'
            319 => 276, // 'Kabel Frecon 150m-Nay' -> 'Frecon 150m'
            329 => 276, // 'Kabel Frecon 150m-By' -> 'Frecon 150m'
            321 => 283, // 'Kabel Frecon 200m-Yud' -> 'Frecon 200m'
            316 => 280, // 'kabel pc 50' -> 'Frecon 50m'
            301 => 291, // 'kabel fo 1c -50m (Bekas Layak Pakai)' -> 'Kabel Fo 1c - 50m (Bekas Layak Pakai)'
            302 => 292, // 'kabel fo 1c - 30m (Bekas Layak Pakai)' -> 'Kabel Fo 1c-30m (Bekas Layak Pakai)'
        ];

        foreach ($explicitMerges as $sourceId => $targetId) {
            $source = Material::find($sourceId);
            $target = Material::find($targetId);

            if ($source && $target && $source->id !== $target->id) {
                MaterialService::mergeMaterials($target, $source, [
                    'name' => $target->name,
                ]);
            }
        }

        // 2. Scan otomatis jika masih ada material lain dengan nama alias / tidak standar di area lain
        $allMaterials = Material::where('id', '>', 260)->orderBy('id')->get();
        foreach ($allMaterials as $mat) {
            // Periksa jika material masih ada di database (belum terhapus oleh merge sebelumnya)
            $existingSource = Material::find($mat->id);
            if (!$existingSource) {
                continue;
            }

            $canonicalName = MaterialService::normalizeMaterialName($existingSource->name);
            if ($canonicalName !== '' && strtolower($canonicalName) !== strtolower(trim($existingSource->name))) {
                $canonicalTarget = MaterialService::findByName($canonicalName, $existingSource->id);
                if ($canonicalTarget && $canonicalTarget->id !== $existingSource->id) {
                    MaterialService::mergeMaterials($canonicalTarget, $existingSource, [
                        'name' => $canonicalTarget->name,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrasi ini bersifat konsolidasi data satu arah (one-way data consolidation)
    }
};
