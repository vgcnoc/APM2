<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci konfigurasi hak akses role yang sudah diatur admin.
 *
 * Role dengan `permissions_customized_at` terisi TIDAK AKAN PERNAH ditimpa ulang ke
 * template preset oleh `permissions:sync` (termasuk opsi --roles / --fresh) maupun
 * seeder, kecuali dipaksa secara eksplisit dengan `--force`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.roles', 'roles');

        if (!Schema::hasColumn($table, 'permissions_customized_at')) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamp('permissions_customized_at')->nullable()->after('guard_name');
            });
        }

        // Seluruh role yang sudah ada di produksi dianggap sudah dikonfigurasi admin → kunci.
        DB::table($table)
            ->whereNull('permissions_customized_at')
            ->whereRaw('LOWER(TRIM(name)) <> ?', ['admin'])
            ->update(['permissions_customized_at' => now()]);
    }

    public function down(): void
    {
        $table = config('permission.table_names.roles', 'roles');
        if (Schema::hasColumn($table, 'permissions_customized_at')) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('permissions_customized_at');
            });
        }
    }
};

