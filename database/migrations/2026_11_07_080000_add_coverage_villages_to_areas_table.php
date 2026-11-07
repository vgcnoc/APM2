<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('areas', 'coverage_villages')) {
            Schema::table('areas', function (Blueprint $table) {
                $table->text('coverage_villages')->nullable()->after('address');
            });
        }

        // Isi data cakupan awal sesuai panduan perusahaan
        $defaults = [
            'BC 1' => 'Desa Sirnajaya, Desa Bantarkalong, Hegarmanah',
            'BC 3' => 'Desa Warungkiara, Desa Tarisi',
            'BC 2' => 'Desa Cimanggu, Desa Cikembar, Bojongkembar',
            'BC 4' => 'Desa Cibadak, Karangtengah, Sukasirna',
        ];

        foreach ($defaults as $areaName => $villages) {
            DB::table('areas')
                ->where('name', $areaName)
                ->whereNull('coverage_villages')
                ->update(['coverage_villages' => $villages]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('areas', 'coverage_villages')) {
            Schema::table('areas', function (Blueprint $table) {
                $table->dropColumn('coverage_villages');
            });
        }
    }
};

