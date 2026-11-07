<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            if (!Schema::hasColumn('resellers', 'can_sell_vouchers')) {
                // Default false karena sementara jualan voucher belum dibuka
                $table->boolean('can_sell_vouchers')->default(false)->after('tier');
            }
            if (!Schema::hasColumn('resellers', 'can_pay_bills')) {
                // Default true karena pembayaran internet bulanan aktif
                $table->boolean('can_pay_bills')->default(true)->after('can_sell_vouchers');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn(['can_sell_vouchers', 'can_pay_bills']);
        });
    }
};

