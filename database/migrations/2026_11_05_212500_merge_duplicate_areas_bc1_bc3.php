<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Area;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Dapatkan model Area target standar
        $bc1 = Area::where('name', 'BC 1')->first();
        $bc3 = Area::where('name', 'BC 3')->first();
        $bc2 = Area::where('name', 'BC 2')->first();
        $bc4 = Area::where('name', 'BC 4')->first();

        $targetBc1Id = $bc1 ? $bc1->id : 2;
        $targetBc3Id = $bc3 ? $bc3->id : 3;
        $targetBc2Id = $bc2 ? $bc2->id : 1;
        $targetBc4Id = $bc4 ? $bc4->id : 6;

        // Ambil ID area duplikat (BC-1 dan BC-3) jika ada
        $dupeBc1Ids = Area::whereIn('name', ['BC-1', 'bc-1', 'BC - 1'])
            ->where('id', '!=', $targetBc1Id)
            ->pluck('id')
            ->toArray();

        $dupeBc3Ids = Area::whereIn('name', ['BC-3', 'bc-3', 'BC - 3'])
            ->where('id', '!=', $targetBc3Id)
            ->pluck('id')
            ->toArray();

        // Sertakan ID eksplisit 7 untuk BC-1 dan 8 untuk BC-3 jika masih ada
        if (Area::where('id', 7)->exists() && !in_array(7, $dupeBc1Ids) && $targetBc1Id !== 7) {
            $dupeBc1Ids[] = 7;
        }
        if (Area::where('id', 8)->exists() && !in_array(8, $dupeBc3Ids) && $targetBc3Id !== 8) {
            $dupeBc3Ids[] = 8;
        }

        // 2. Daftar tabel yang memiliki kolom area_id untuk di-reassign
        $tablesWithAreaId = [
            'customers' => 'area_id',
            'material_transactions' => 'area_id',
            'surveys' => 'area_id',
            'onts' => 'area_id',
            'technician_schedules' => 'area_id',
            'users' => 'area_id',
            'employees' => 'area_id',
            'expenses' => 'area_id',
            'incomes' => 'area_id',
            'attendances' => 'area_id',
            'routers' => 'area_id',
            'odcs' => 'area_id',
            'odps' => 'area_id',
            'olts' => 'area_id',
            'area_olt' => 'area_id',
            'material_stocks' => 'area_id',
            'ftth_designs' => 'area_id',
            'network_maintenances' => 'area_id',
            'infrastructure_work_orders' => 'area_id',
            'materials' => 'area_id',
            'pgk_tickets' => 'area_id',
            'pgj_tickets' => 'area_id',
            'pgs_tickets' => 'area_id',
            'wa_campaigns' => 'target_area_id',
        ];

        // Reassign relasi BC-1 ke target BC 1
        if (!empty($dupeBc1Ids)) {
            foreach ($tablesWithAreaId as $table => $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    DB::table($table)->whereIn($column, $dupeBc1Ids)->update([$column => $targetBc1Id]);
                }
            }
        }

        // Reassign relasi BC-3 ke target BC 3
        if (!empty($dupeBc3Ids)) {
            foreach ($tablesWithAreaId as $table => $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    DB::table($table)->whereIn($column, $dupeBc3Ids)->update([$column => $targetBc3Id]);
                }
            }
        }

        // 3. Normalisasi teks area string di customers dan material_transactions
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'area')) {
            DB::table('customers')->where('area_id', $targetBc1Id)->orWhere('area', 'BC-1')->update(['area' => 'BC 1', 'area_id' => $targetBc1Id]);
            DB::table('customers')->where('area_id', $targetBc3Id)->orWhere('area', 'BC-3')->update(['area' => 'BC 3', 'area_id' => $targetBc3Id]);
            DB::table('customers')->where('area_id', $targetBc2Id)->orWhere('area', 'BC-2')->update(['area' => 'BC 2', 'area_id' => $targetBc2Id]);
            if ($targetBc4Id) {
                DB::table('customers')->where('area_id', $targetBc4Id)->orWhere('area', 'BC-4')->update(['area' => 'BC 4', 'area_id' => $targetBc4Id]);
            }
        }

        if (Schema::hasTable('material_transactions') && Schema::hasColumn('material_transactions', 'area')) {
            DB::table('material_transactions')->where('area_id', $targetBc1Id)->orWhere('area', 'BC-1')->update(['area' => 'BC 1', 'area_id' => $targetBc1Id]);
            DB::table('material_transactions')->where('area_id', $targetBc3Id)->orWhere('area', 'BC-3')->update(['area' => 'BC 3', 'area_id' => $targetBc3Id]);
            DB::table('material_transactions')->where('area_id', $targetBc2Id)->orWhere('area', 'BC-2')->update(['area' => 'BC 2', 'area_id' => $targetBc2Id]);
        }

        // 4. Hapus duplikat dari tabel areas
        $allDupeIds = array_unique(array_merge($dupeBc1Ids, $dupeBc3Ids));
        if (!empty($allDupeIds)) {
            DB::table('areas')->whereIn('id', $allDupeIds)->delete();
        }
        DB::table('areas')->whereIn('name', ['BC-1', 'BC-3'])->whereNotIn('id', [$targetBc1Id, $targetBc3Id])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Operasi konsolidasi data master & normalisasi, tidak diperlukan rollback
    }
};
