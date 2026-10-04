<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('voucher_profiles', 'fee_admin')) {
                $table->decimal('fee_admin', 15, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('voucher_profiles', 'fee_reseller')) {
                $table->decimal('fee_reseller', 15, 2)->default(0)->after('fee_admin');
            }
            if (!Schema::hasColumn('voucher_profiles', 'fee_partner')) {
                $table->decimal('fee_partner', 15, 2)->default(0)->after('fee_reseller');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voucher_profiles', function (Blueprint $table) {
            $table->dropColumn(['fee_admin', 'fee_reseller', 'fee_partner']);
        });
    }
};
