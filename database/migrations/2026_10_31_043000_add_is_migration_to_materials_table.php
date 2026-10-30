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
        if (!Schema::hasColumn('materials', 'is_migration')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->boolean('is_migration')->default(false)->index()->after('is_active');
            });
        }

        // Tandai seluruh material yang dihasilkan dari proses migrasi pelanggan/sistem lama
        DB::table('materials')
            ->where('id', '<=', 260)
            ->orWhere('description', 'like', '%migrasi%')
            ->orWhere('description', 'like', '%hasil migrasi%')
            ->orWhere('supplier', 'like', '%migrasi%')
            ->orWhere('supplier', 'like', '%Sistem Migrasi%')
            ->update(['is_migration' => true]);

        // Pastikan material katalog murni yang diinput / direstock bernilai false
        DB::table('materials')
            ->where('id', '>', 260)
            ->where(function ($q) {
                $q->whereNull('description')
                  ->orWhere(function ($dq) {
                      $dq->where('description', 'not like', '%migrasi%')
                         ->where('description', 'not like', '%hasil migrasi%');
                  });
            })
            ->update(['is_migration' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('materials', 'is_migration')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropColumn('is_migration');
            });
        }
    }
};

