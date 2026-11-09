<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\Area;
use App\Models\Ont;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Daftarkan Master Material Katalog "ONT ZTE F660" jika belum ada
        $material = Material::where('name', 'ONT ZTE F660')->first();
        if (!$material) {
            $material = Material::create([
                'name' => 'ONT ZTE F660',
                'category' => 'ONT',
                'unit' => 'unit',
                'stock' => 0,
                'initial_stock' => 0,
                'price_per_unit' => 165000,
                'selling_price' => 185000,
                'description' => 'Modem ONT ZTE F660 GPON / EPON Standar ISP',
                'is_active' => true,
                'is_migration' => false,
                'is_catalog' => true,
                'requires_sn' => true,
                'requires_mac' => true,
            ]);
        } else {
            $material->is_catalog = true;
            $material->is_migration = false;
            $material->is_active = true;
            $material->requires_sn = true;
            $material->requires_mac = true;
            $material->saveQuietly();
        }

        // 2. Buat Alokasi Stok Gudang per Area (BC 1, BC 2, BC 3, BC 4) agar tampil di Stok Barang per Area
        $areas = Area::all();
        foreach ($areas as $area) {
            MaterialStock::firstOrCreate(
                [
                    'material_id' => $material->id,
                    'area_id' => $area->id,
                ],
                [
                    'stock' => 0,
                    'initial_stock' => 0,
                    'total_rolls' => 0,
                    'total_packs' => 0,
                    'total_pieces' => 0,
                ]
            );
        }

        // 3. Standarisasi Brand & Model pada seluruh data perangkat ONT F660
        Ont::where(function ($q) {
            $q->where('brand', 'like', '%660%')
              ->orWhere('model', 'like', '%660%');
        })->get()->each(function ($ont) {
            $ont->brand = 'ZTE';
            $ont->model = 'F660';
            $ont->saveQuietly();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep data intact
    }
};

