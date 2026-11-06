<?php

use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransactionItem;
use App\Models\CustomerInstalledMaterial;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Pulihkan ke-9 master material dengan ID dan penamaan aslinya (termasuk kode belanja online & catatan migrasi)
            $materialsData = [
                [
                    'id' => 330,
                    'name' => 'Kabel Frecon 100m (migrasi)',
                    'category' => 'Kabel',
                    'unit' => 'meter',
                    'meter_per_roll' => 100.00,
                    'total_rolls' => 1.00,
                    'initial_stock' => 100.00,
                    'stock' => 100.00,
                    'price_per_unit' => 0.00,
                    'selling_price' => 0.00,
                    'is_active' => 1,
                    'is_migration' => 0,
                    'is_catalog' => 0,
                    'created_at' => '2026-11-05 13:32:26',
                    'updated_at' => '2026-11-05 13:32:26',
                ],
                [
                    'id' => 320,
                    'name' => 'Kabel Frecon 100m-NAY',
                    'category' => 'Kabel',
                    'unit' => 'meter',
                    'meter_per_roll' => 100.00,
                    'total_rolls' => 0.00,
                    'initial_stock' => 100.00,
                    'stock' => 0.00,
                    'price_per_unit' => 0.00,
                    'selling_price' => 0.00,
                    'is_active' => 1,
                    'is_migration' => 0,
                    'is_catalog' => 1,
                    'created_at' => '2026-11-05 13:30:00',
                    'updated_at' => '2026-11-05 13:30:00',
                ],
                [
                    'id' => 318,
                    'name' => 'Kabel Frecon 150m-A12',
                    'category' => 'Kabel',
                    'unit' => 'meter',
                    'meter_per_roll' => 150.00,
                    'total_rolls' => 1.99,
                    'initial_stock' => 450.00,
                    'stock' => 298.00,
                    'price_per_unit' => 0.00,
                    'selling_price' => 0.00,
                    'is_active' => 1,
                    'is_migration' => 0,
                    'is_catalog' => 1,
                    'created_at' => '2026-11-05 08:40:00',
                    'updated_at' => '2026-11-05 08:40:00',
                ],
                [
                    'id' => 319,
                    'name' => 'Kabel Frecon 150m-Nay',
                    'category' => 'Kabel',
                    'unit' => 'meter',
                    'meter_per_roll' => 150.00,
                    'total_rolls' => 0.00,
                    'initial_stock' => 150.00,
                    'stock' => 0.00,
                    'price_per_unit' => 0.00,
                    'selling_price' => 0.00,
                    'is_active' => 1,
                    'is_migration' => 0,
                    'is_catalog' => 1,
                    'created_at' => '2026-11-04 18:00:00',
                    'updated_at' => '2026-11-04 18:00:00',
                ],
                [
                    'id' => 329,
                    'name' => 'Kabel Frecon 150m-By',
                    'category' => 'Kabel',
                    'unit' => 'meter',
                    'meter_per_roll' => 150.00,
                    'total_rolls' => 1.00,
                    'initial_stock' => 600.00,
                    'stock' => 150.00,
                    'price_per_unit' => 0.00,
                    'selling_price' => 0.00,
                    'is_active' => 1,
                    'is_migration' => 0,
                    'is_catalog' => 1,
                    'created_at' => '2026-11-05 16:15:00',
                    'updated_at' => '2026-11-05 16:15:00',
                ],
                [
                    'id' => 321,
                    'name' => 'Kabel Frecon 200m-Yud',
                    'category' => 'Kabel',
                    'unit' => 'meter',
                    'meter_per_roll' => 200.00,
                    'total_rolls' => 0.00,
                    'initial_stock' => 400.00,
                    'stock' => 0.00,
                    'price_per_unit' => 0.00,
                    'selling_price' => 0.00,
                    'is_active' => 1,
                    'is_migration' => 0,
                    'is_catalog' => 1,
                    'created_at' => '2026-11-05 19:10:00',
                    'updated_at' => '2026-11-05 19:10:00',
                ],
                [
                    'id' => 316,
                    'name' => 'kabel pc 50',
                    'category' => 'Kabel',
                    'unit' => 'meter',
                    'meter_per_roll' => 50.00,
                    'total_rolls' => 0.00,
                    'initial_stock' => 100.00,
                    'stock' => 0.00,
                    'price_per_unit' => 0.00,
                    'selling_price' => 0.00,
                    'is_active' => 1,
                    'is_migration' => 0,
                    'is_catalog' => 0,
                    'created_at' => '2026-11-04 07:24:58',
                    'updated_at' => '2026-11-04 07:24:58',
                ],
                [
                    'id' => 301,
                    'name' => 'kabel fo 1c -50m (Bekas Layak Pakai)',
                    'category' => 'Kabel',
                    'unit' => 'meter',
                    'meter_per_roll' => 50.00,
                    'total_rolls' => 1.00,
                    'initial_stock' => 50.00,
                    'stock' => 50.00,
                    'price_per_unit' => 0.00,
                    'selling_price' => 0.00,
                    'is_active' => 1,
                    'is_migration' => 0,
                    'is_catalog' => 0,
                    'created_at' => '2026-11-02 12:25:20',
                    'updated_at' => '2026-11-02 12:25:20',
                ],
                [
                    'id' => 302,
                    'name' => 'kabel fo 1c - 30m (Bekas Layak Pakai)',
                    'category' => 'Kabel',
                    'unit' => 'meter',
                    'meter_per_roll' => 30.00,
                    'total_rolls' => 1.00,
                    'initial_stock' => 60.00,
                    'stock' => 30.00,
                    'price_per_unit' => 0.00,
                    'selling_price' => 0.00,
                    'is_active' => 1,
                    'is_migration' => 0,
                    'is_catalog' => 0,
                    'created_at' => '2026-11-02 12:27:02',
                    'updated_at' => '2026-11-02 12:27:02',
                ],
            ];

            foreach ($materialsData as $data) {
                DB::table('materials')->updateOrInsert(['id' => $data['id']], $data);
            }

            // 2. Kembalikan asosiasi item transaksi (MaterialTransactionItem) ke ID dan nama aslinya
            $itemMappings = [
                3844 => [330, 'Kabel Frecon 100m (migrasi)'],
                3854 => [320, 'Kabel Frecon 100m-NAY'],
                3847 => [320, 'Kabel Frecon 100m-NAY'],
                3930 => [320, 'Kabel Frecon 100m-NAY'],
                3792 => [318, 'Kabel Frecon 150m-A12'],
                3813 => [318, 'Kabel Frecon 150m-A12'],
                3842 => [318, 'Kabel Frecon 150m-A12'],
                3860 => [318, 'Kabel Frecon 150m-A12'],
                3779 => [319, 'Kabel Frecon 150m-Nay'],
                3800 => [319, 'Kabel Frecon 150m-Nay'],
                3866 => [329, 'Kabel Frecon 150m-By'],
                3864 => [329, 'Kabel Frecon 150m-By'],
                3908 => [329, 'Kabel Frecon 150m-By'],
                3916 => [329, 'Kabel Frecon 150m-By'],
                3922 => [329, 'Kabel Frecon 150m-By'],
                3862 => [321, 'Kabel Frecon 200m-Yud'],
                3941 => [321, 'Kabel Frecon 200m-Yud'],
                3920 => [321, 'Kabel Frecon 200m-Yud'],
                3935 => [321, 'Kabel Frecon 200m-Yud'],
                3950 => [321, 'Kabel Frecon 200m-Yud'],
                3749 => [316, 'kabel pc 50'],
                3750 => [316, 'kabel pc 50'],
                3840 => [316, 'kabel pc 50'],
                3623 => [301, 'kabel fo 1c -50m (Bekas Layak Pakai)'],
                3624 => [302, 'kabel fo 1c - 30m (Bekas Layak Pakai)'],
                3807 => [302, 'kabel fo 1c - 30m (Bekas Layak Pakai)'],
            ];

            foreach ($itemMappings as $itemId => [$matId, $matName]) {
                DB::table('material_transaction_items')->where('id', $itemId)->update([
                    'material_id' => $matId,
                    'material_name' => $matName,
                ]);
            }

            // 3. Kembalikan relasi material instalasi pelanggan (CustomerInstalledMaterial)
            $installedMappings = [
                3847 => 320,
                3930 => 320,
                3842 => 318,
                3860 => 318,
                3800 => 319,
                3908 => 329,
                3916 => 329,
                3922 => 329,
                3920 => 321,
                3935 => 321,
                3840 => 316,
            ];

            foreach ($installedMappings as $itemId => $matId) {
                DB::table('customer_installed_materials')->where('material_transaction_item_id', $itemId)->update([
                    'material_id' => $matId,
                ]);
            }

            // Kembalikan PGJ & PGK material jika ada
            DB::table('pgj_materials')->where('material_transaction_item_id', 3750)->update(['material_id' => 316]);
            DB::table('pgk_materials')->where('material_transaction_item_id', 3807)->update(['material_id' => 302]);

            // 4. Pulihkan stok area masing-masing (material_stocks)
            $stocks = [
                // 330: Area 2 (BC 1)
                ['material_id' => 330, 'area_id' => 2, 'stock' => 100.00, 'initial_stock' => 100.00, 'total_rolls' => 1.00],
                // 320: Area 2 (BC 1)
                ['material_id' => 320, 'area_id' => 2, 'stock' => 0.00, 'initial_stock' => 100.00, 'total_rolls' => 0.00],
                // 318: Area 3 (BC 3) & Area 2 (BC 1)
                ['material_id' => 318, 'area_id' => 3, 'stock' => 298.00, 'initial_stock' => 450.00, 'total_rolls' => 1.99],
                ['material_id' => 318, 'area_id' => 2, 'stock' => 0.00, 'initial_stock' => 0.00, 'total_rolls' => 0.00],
                // 319: Area 2 (BC 1)
                ['material_id' => 319, 'area_id' => 2, 'stock' => 0.00, 'initial_stock' => 150.00, 'total_rolls' => 0.00],
                // 329: Area 2 (BC 1)
                ['material_id' => 329, 'area_id' => 2, 'stock' => 150.00, 'initial_stock' => 600.00, 'total_rolls' => 1.00],
                // 321: Area 2 (BC 1) & Area 3 (BC 3)
                ['material_id' => 321, 'area_id' => 2, 'stock' => 0.00, 'initial_stock' => 200.00, 'total_rolls' => 0.00],
                ['material_id' => 321, 'area_id' => 3, 'stock' => 0.00, 'initial_stock' => 400.00, 'total_rolls' => 0.00],
                // 316: Area 2 (BC 1)
                ['material_id' => 316, 'area_id' => 2, 'stock' => 0.00, 'initial_stock' => 100.00, 'total_rolls' => 0.00],
                // 301: Area 3 (BC 3)
                ['material_id' => 301, 'area_id' => 3, 'stock' => 50.00, 'initial_stock' => 50.00, 'total_rolls' => 1.00],
                // 302: Area 3 (BC 3)
                ['material_id' => 302, 'area_id' => 3, 'stock' => 30.00, 'initial_stock' => 60.00, 'total_rolls' => 1.00],
            ];

            foreach ($stocks as $st) {
                DB::table('material_stocks')->updateOrInsert(
                    ['material_id' => $st['material_id'], 'area_id' => $st['area_id']],
                    [
                        'stock' => $st['stock'],
                        'initial_stock' => $st['initial_stock'],
                        'total_rolls' => $st['total_rolls'],
                        'total_packs' => 0,
                        'total_pieces' => 0,
                    ]
                );
            }

            // 5. Kembalikan stok material standar (279, 276, 283, 280, 291, 292) ke angka aslinya
            // 279 (Frecon 100m): Hapus alokasi Area 2 (BC 1) karena 100m tersebut adalah milik ID 330
            DB::table('material_stocks')->where('material_id', 279)->where('area_id', 2)->delete();

            // 276 (Frecon 150m): Area 2 stok = 0, Area 3 stok = 0
            DB::table('material_stocks')->where('material_id', 276)->where('area_id', 2)->update([
                'stock' => 0.00,
                'initial_stock' => 450.00,
                'total_rolls' => 0.00,
            ]);
            DB::table('material_stocks')->where('material_id', 276)->where('area_id', 3)->update([
                'stock' => 0.00,
                'initial_stock' => 600.00,
                'total_rolls' => 0.00,
            ]);

            // 283 (Frecon 200m): Area 2 stok = 0, Area 3 stok = 1
            DB::table('material_stocks')->where('material_id', 283)->where('area_id', 2)->update([
                'stock' => 0.00,
                'initial_stock' => 400.00,
                'total_rolls' => 0.00,
            ]);
            DB::table('material_stocks')->where('material_id', 283)->where('area_id', 3)->update([
                'stock' => 1.00,
                'initial_stock' => 200.00,
                'total_rolls' => 0.01,
            ]);

            // 280 (Frecon 50m): Area 2 stok = 50, initial_stock = 100
            DB::table('material_stocks')->where('material_id', 280)->where('area_id', 2)->update([
                'stock' => 50.00,
                'initial_stock' => 100.00,
                'total_rolls' => 1.00,
            ]);

            // 291 (Kabel Fo 1c - 50m (Bekas Layak Pakai)): Area 3 stok = 150
            DB::table('material_stocks')->where('material_id', 291)->where('area_id', 3)->update([
                'stock' => 150.00,
                'initial_stock' => 150.00,
                'total_rolls' => 3.00,
            ]);

            // 292 (Kabel Fo 1c-30m (Bekas Layak Pakai)): Hapus Area 3 (karena milik ID 302)
            DB::table('material_stocks')->where('material_id', 292)->where('area_id', 3)->delete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
