<?php

use App\Models\Material;
use App\Models\MaterialStock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pastikan seluruh material fisik non-legacy (ID > 260) memiliki is_migration = false
        Material::where('id', '>', 260)->update(['is_migration' => false]);

        // 2. Pulihkan sisa stok fisik riil untuk material BC 2 & BC 3 yang sebelumnya ter-reset ke 0
        $restorations = [
            // MAT #291: Kabel Fo 1c - 50m
            [
                'material_id' => 291,
                'meter_per_roll' => 50,
                'stocks' => [
                    ['area_id' => 1, 'stock' => 100, 'initial_stock' => 100, 'total_rolls' => 2], // BC 2 (in 150 - out 50 = 100)
                    ['area_id' => 3, 'stock' => 150, 'initial_stock' => 150, 'total_rolls' => 3], // BC 3 (in 150 - out 0 = 150)
                ],
                'global_stock' => 250,
                'global_initial' => 250,
                'global_rolls' => 5,
            ],
            // MAT #292: Kabel Fo 1c-30m
            [
                'material_id' => 292,
                'meter_per_roll' => 30,
                'stocks' => [
                    ['area_id' => 1, 'stock' => 120, 'initial_stock' => 120, 'total_rolls' => 4], // BC 2 (in 120 - out 0 = 120)
                ],
                'global_stock' => 120,
                'global_initial' => 120,
                'global_rolls' => 4,
            ],
            // MAT #293: Kabel Fo 1c-25m
            [
                'material_id' => 293,
                'meter_per_roll' => 25,
                'stocks' => [
                    ['area_id' => 1, 'stock' => 25, 'initial_stock' => 25, 'total_rolls' => 1], // BC 2 (in 25 - out 0 = 25)
                ],
                'global_stock' => 25,
                'global_initial' => 25,
                'global_rolls' => 1,
            ],
            // MAT #294: Adaptor 12v 1A
            [
                'material_id' => 294,
                'meter_per_roll' => 0,
                'stocks' => [
                    ['area_id' => 1, 'stock' => 6, 'initial_stock' => 6, 'total_rolls' => 0], // BC 2 (in 6 - out 0 = 6)
                ],
                'global_stock' => 6,
                'global_initial' => 6,
                'global_rolls' => 0,
            ],
            // MAT #295: kabel fo 1c - 90m
            [
                'material_id' => 295,
                'meter_per_roll' => 90,
                'stocks' => [
                    ['area_id' => 3, 'stock' => 90, 'initial_stock' => 90, 'total_rolls' => 1], // BC 3 (in 90 - out 0 = 90)
                ],
                'global_stock' => 90,
                'global_initial' => 90,
                'global_rolls' => 1,
            ],
            // MAT #297: kabel fo 1c - 100m
            [
                'material_id' => 297,
                'meter_per_roll' => 100,
                'stocks' => [
                    ['area_id' => 3, 'stock' => 200, 'initial_stock' => 300, 'total_rolls' => 2], // BC 3 (in 300 - out 100 = 200)
                ],
                'global_stock' => 200,
                'global_initial' => 300,
                'global_rolls' => 2,
            ],
            // MAT #301: kabel fo 1c -50m
            [
                'material_id' => 301,
                'meter_per_roll' => 50,
                'stocks' => [
                    ['area_id' => 3, 'stock' => 50, 'initial_stock' => 50, 'total_rolls' => 1], // BC 3 (in 50 - out 0 = 50)
                ],
                'global_stock' => 50,
                'global_initial' => 50,
                'global_rolls' => 1,
            ],
            // MAT #302: kabel fo 1c - 30m
            [
                'material_id' => 302,
                'meter_per_roll' => 30,
                'stocks' => [
                    ['area_id' => 3, 'stock' => 60, 'initial_stock' => 60, 'total_rolls' => 2], // BC 3 (in 60 - out 0 = 60)
                ],
                'global_stock' => 60,
                'global_initial' => 60,
                'global_rolls' => 2,
            ],
            // MAT #304: kabel fo 1c - 70m
            [
                'material_id' => 304,
                'meter_per_roll' => 70,
                'stocks' => [
                    ['area_id' => 3, 'stock' => 140, 'initial_stock' => 140, 'total_rolls' => 2], // BC 3 (in 140 - out 0 = 140)
                ],
                'global_stock' => 140,
                'global_initial' => 140,
                'global_rolls' => 2,
            ],
            // MAT #269: ONT H609e (Tanpa Adaptor)
            [
                'material_id' => 269,
                'meter_per_roll' => 0,
                'stocks' => [
                    ['area_id' => 1, 'stock' => 2, 'initial_stock' => 2, 'total_rolls' => 0], // BC 2 (in 2 - out 0 = 2)
                    ['area_id' => 3, 'stock' => 1, 'initial_stock' => 2, 'total_rolls' => 0], // BC 3 (in 2 - out 1 = 1)
                ],
                'global_stock' => 3,
                'global_initial' => 4,
                'global_rolls' => 0,
            ],
        ];

        foreach ($restorations as $item) {
            $mat = Material::find($item['material_id']);
            if (!$mat) {
                continue;
            }

            $mat->is_migration = false;
            $mat->is_active = true;
            $mat->stock = $item['global_stock'];
            $mat->initial_stock = $item['global_initial'];
            if ($item['meter_per_roll'] > 0) {
                $mat->meter_per_roll = $item['meter_per_roll'];
                $mat->total_rolls = $item['global_rolls'];
            }
            $mat->save();

            foreach ($item['stocks'] as $sData) {
                MaterialStock::updateOrCreate(
                    [
                        'material_id' => $mat->id,
                        'area_id' => $sData['area_id'],
                    ],
                    [
                        'stock' => $sData['stock'],
                        'initial_stock' => $sData['initial_stock'],
                        'total_rolls' => $sData['total_rolls'] ?? 0,
                        'total_packs' => 0,
                        'total_pieces' => 0,
                    ]
                );
            }
        }

        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

