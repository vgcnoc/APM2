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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method', 50)->default('transfer')->change();
            if (!Schema::hasColumn('payments', 'payment_channel')) {
                $table->string('payment_channel', 50)->nullable()->after('payment_method');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_ref')) {
                $table->string('payment_ref', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('invoices', 'payment_url')) {
                $table->text('payment_url')->nullable()->after('payment_ref');
            }
            if (!Schema::hasColumn('invoices', 'payment_channel')) {
                $table->string('payment_channel', 50)->nullable()->after('payment_url');
            }
            if (!Schema::hasColumn('invoices', 'payment_gateway_payload')) {
                $table->json('payment_gateway_payload')->nullable()->after('payment_channel');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_ref', 'payment_url', 'payment_channel', 'payment_gateway_payload']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_channel']);
        });
    }
};
