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
        if (!Schema::hasColumn('materials', 'is_catalog')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->boolean('is_catalog')->default(false)->after('is_migration');
            });
        }

        // Tandai produk katalog resmi (Gudang Pusat / Order Toko) yang murni untuk belanja toko:
        // id > 260, bukan migrasi, bukan barang bekas/pencabutan/operasional area
        DB::table('materials')
            ->where('id', '>', 260)
            ->where(function ($q) {
                $q->where('is_migration', false)->orWhereNull('is_migration');
            })
            ->where('name', 'not like', '%(Bekas%')
            ->where('name', 'not like', '%kabel bekas%')
            ->where('supplier', 'not like', 'BC%')
            ->where('supplier', 'not like', 'Bc%')
            ->where('supplier', 'not like', 'kabel pc%')
            ->where(function ($dq) {
                $dq->whereNull('description')
                   ->orWhere(function ($sub) {
                       $sub->where('description', 'not like', '%pencabutan%')
                           ->where('description', 'not like', '%bekas%')
                           ->where('description', 'not like', '%non-toko%');
                   });
            })
            ->update(['is_catalog' => true]);

        // Pastikan material operasional/fisik area lokal dan migrasi selalu is_catalog = false
        DB::table('materials')
            ->where('is_migration', true)
            ->orWhere('id', '<=', 260)
            ->orWhere('name', 'like', '%(Bekas%')
            ->orWhere('name', 'like', '%kabel bekas%')
            ->orWhere('supplier', 'like', 'BC%')
            ->orWhere('supplier', 'like', 'Bc%')
            ->orWhere('supplier', 'like', 'kabel pc%')
            ->orWhere('description', 'like', '%pencabutan%')
            ->orWhere('description', 'like', '%bekas%')
            ->orWhere('description', 'like', '%non-toko%')
            ->update(['is_catalog' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('materials', 'is_catalog')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropColumn('is_catalog');
            });
        }
    }
};
