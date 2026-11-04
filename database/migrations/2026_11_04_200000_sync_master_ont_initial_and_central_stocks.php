<?php

use App\Models\Material;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sesuaikan ONT ZVOLIN P613s ( Non Adaptor ) (ID 282):
        // Pembelian riil dari Dedi Suplier = 52 PCS (1 karton/dus isi 50 + 2).
        // Total keluar via Order Toko ke cabang = 17 PCS (BC 2: 12, BC 1: 3, BC 3: 2).
        // Sisa fisik di Gudang Pusat = 52 - 17 = 35 PCS.
        // Sisa fisik di cabang = 5 PCS (BC 1: 3, BC 2: 1, BC 3: 1). Total fisik riil = 40 PCS.
        $m282 = Material::find(282);
        if ($m282) {
            $m282->initial_stock = 52;
            $m282->stock = 35;
            $m282->recalcUnitTotals()->save();
        }

        // 2. Sesuaikan ONT H690e ( Non Adaptor ) (ID 286):
        // Pembelian riil = 20 PCS.
        // Total keluar via Order Toko ke cabang = 10 PCS (PO BC 1: 1 pcs + 9 pcs).
        // Sisa fisik di Gudang Pusat = 20 - 10 = 10 PCS.
        // Sisa fisik di cabang BC 1 = 2 PCS. Total fisik riil = 12 PCS.
        $m286 = Material::find(286);
        if ($m286) {
            $m286->initial_stock = 20;
            $m286->stock = 10;
            $m286->recalcUnitTotals()->save();
        }

        // 3. Sesuaikan ONT H609e (Tanpa Adaptor) (ID 269):
        // Pembelian riil = 4 PCS.
        // Total keluar via Order Toko ke cabang = 4 PCS (BC 2: 2, BC 3: 2).
        // Sisa fisik di Gudang Pusat = 4 - 4 = 0 PCS.
        // Sisa fisik di cabang = 3 PCS (BC 2: 2, BC 3: 1). Total fisik riil = 3 PCS.
        $m269 = Material::find(269);
        if ($m269) {
            $m269->initial_stock = 4;
            $m269->stock = 0;
            $m269->recalcUnitTotals()->save();
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

