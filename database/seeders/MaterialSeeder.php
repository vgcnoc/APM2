<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = [
            [
                'name' => 'Kabel Drop Core 1 Core FiberStar',
                'category' => 'Kabel',
                'supplier' => 'FiberStar',
                'unit' => 'meter',
                'stock' => 0,
                'meter_per_roll' => 1000,
                'pcs_per_pack' => null,
                'cm_per_pcs' => null,
                'description' => 'Kabel FO untuk tarikan rumah pelanggan.',
            ],
            [
                'name' => 'Kabel Drop Core 2 Core Zimmlink',
                'category' => 'Kabel',
                'supplier' => 'Zimmlink',
                'unit' => 'meter',
                'stock' => 0,
                'meter_per_roll' => 1000,
                'pcs_per_pack' => null,
                'cm_per_pcs' => null,
                'description' => 'Kabel FO 2 Core.',
            ],
            [
                'name' => 'Kabel Precon 50 Meter',
                'category' => 'Kabel',
                'supplier' => 'Generic',
                'unit' => 'pcs',
                'stock' => 0,
                'meter_per_roll' => null,
                'pcs_per_pack' => null,
                'cm_per_pcs' => 5000,
                'description' => 'Kabel FO Precon sudah ada ujung konektor.',
            ],
            [
                'name' => 'ONT ZTE F609',
                'category' => 'ONT',
                'supplier' => 'ZTE',
                'unit' => 'pcs',
                'stock' => 0,
                'meter_per_roll' => null,
                'pcs_per_pack' => 20, // 1 Dus isi 20
                'cm_per_pcs' => null,
                'description' => 'Modem ONT ZTE F609.',
            ],
            [
                'name' => 'Klem Kabel FO (Paku Beton)',
                'category' => 'Aksesoris',
                'supplier' => 'Generic',
                'unit' => 'pcs',
                'stock' => 0,
                'meter_per_roll' => null,
                'pcs_per_pack' => 100, // 1 Pack isi 100 pcs
                'cm_per_pcs' => null,
                'description' => 'Klem paku beton untuk merapikan kabel.',
            ],
            [
                'name' => 'Isolasi Listrik Hitam Nitto',
                'category' => 'Aksesoris',
                'supplier' => 'Nitto',
                'unit' => 'pcs',
                'stock' => 0,
                'meter_per_roll' => null,
                'pcs_per_pack' => 10, // 1 Slop isi 10
                'cm_per_pcs' => null,
                'description' => 'Isolasi listrik.',
            ],
            [
                'name' => 'Splitter 1:8 Box',
                'category' => 'Splitter',
                'supplier' => 'Generic',
                'unit' => 'pcs',
                'stock' => 0,
                'meter_per_roll' => null,
                'pcs_per_pack' => null,
                'cm_per_pcs' => null,
                'description' => 'ODP Splitter Box 1:8.',
            ],
            [
                'name' => 'Patch Cord Fiber Optic SC UPC-UPC',
                'category' => 'Aksesoris',
                'supplier' => 'Generic',
                'unit' => 'pcs',
                'stock' => 0,
                'meter_per_roll' => null,
                'pcs_per_pack' => null,
                'cm_per_pcs' => 100,
                'description' => 'Kabel patch cord kuning 1 meter.',
            ],
        ];

        foreach ($materials as $m) {
            \App\Models\Material::firstOrCreate(['name' => $m['name']], $m);
        }
    }
}
