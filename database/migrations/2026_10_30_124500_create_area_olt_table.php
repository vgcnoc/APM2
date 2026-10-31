<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_olt', function (Blueprint $table) {
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('olt_id')->constrained('olts')->cascadeOnDelete();
            $table->unique(['area_id', 'olt_id']);
        });

        DB::table('olts')
            ->select(['id', 'area_id'])
            ->whereNotNull('area_id')
            ->orderBy('id')
            ->eachById(function ($olt) {
                DB::table('area_olt')->insertOrIgnore([
                    'area_id' => $olt->area_id,
                    'olt_id' => $olt->id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_olt');
    }
};
