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
        // Set stok Pusat ONT ZVOLIN F613 + Adaptor (ID 284) menjadi 0
        // Karena unit fisik (SN: ZICGA58F67F0) aslinya sudah dipasang di pelanggan MAHMUDEH (BC 2)
        // dan telah ditarik sebagai unit rusak pada tiket PGK-20261104-0007.
        $m284 = Material::find(284);
        if ($m284) {
            $m284->stock = 0;
            $m284->recalcUnitTotals()->save();
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
