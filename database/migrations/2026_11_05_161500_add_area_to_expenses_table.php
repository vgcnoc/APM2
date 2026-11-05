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
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (!Schema::hasColumn('expenses', 'area_id')) {
                    $table->foreignId('area_id')->nullable()->after('expense_category_id')->constrained('areas')->nullOnDelete();
                }
                if (!Schema::hasColumn('expenses', 'area')) {
                    $table->string('area')->nullable()->after('area_id');
                }
            });

            // Backfill 1: Sinkronkan pengeluaran yang terikat dengan transaksi Order Toko
            if (Schema::hasTable('material_transactions')) {
                $transactions = DB::table('material_transactions')
                    ->whereNotNull('expense_id')
                    ->get(['id', 'expense_id', 'area_id', 'area', 'technician_name']);

                foreach ($transactions as $trx) {
                    $resolvedArea = $trx->area ?: $trx->technician_name;
                    $areaId = $trx->area_id;

                    if (!$areaId && !empty($resolvedArea)) {
                        $areaId = DB::table('areas')->where('name', $resolvedArea)->value('id');
                    }

                    if ($areaId || $resolvedArea) {
                        DB::table('expenses')
                            ->where('id', $trx->expense_id)
                            ->update([
                                'area_id' => $areaId,
                                'area' => $resolvedArea,
                            ]);
                    }
                }
            }

            // Backfill 2: Sinkronkan pengeluaran yang memuat nama wilayah di keterangannya
            $allAreas = DB::table('areas')->get(['id', 'name']);
            foreach ($allAreas as $a) {
                DB::table('expenses')
                    ->whereNull('area_id')
                    ->where(function ($q) use ($a) {
                        $q->where('description', 'like', '%Wilayah ' . $a->name . '%')
                          ->orWhere('description', 'like', '%[' . $a->name . ']%')
                          ->orWhere('description', 'like', '%Area ' . $a->name . '%');
                    })
                    ->update([
                        'area_id' => $a->id,
                        'area' => $a->name,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (Schema::hasColumn('expenses', 'area_id')) {
                    $table->dropForeign(['area_id']);
                    $table->dropColumn('area_id');
                }
                if (Schema::hasColumn('expenses', 'area')) {
                    $table->dropColumn('area');
                }
            });
        }
    }
};

