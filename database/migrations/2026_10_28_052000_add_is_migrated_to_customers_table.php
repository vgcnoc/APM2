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
        if (!Schema::hasColumn('customers', 'is_migrated')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('is_migrated')->default(false)->after('service_status')->index();
            });
        }

        // Tandai seluruh pelanggan yang ada saat ini sebagai pelanggan hasil migrasi (kecuali yang terdaftar organik dengan referral_code)
        DB::table('customers')
            ->where(function ($q) {
                $q->whereNull('referral_code')
                  ->orWhere('referral_code', '');
            })
            ->update(['is_migrated' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('customers', 'is_migrated')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('is_migrated');
            });
        }
    }
};
