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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'billing_cycle')) {
                $table->string('billing_cycle', 30)->default('default')->after('billing_type');
            }
            if (!Schema::hasColumn('customers', 'billing_day')) {
                $table->unsignedTinyInteger('billing_day')->nullable()->after('billing_cycle');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'billing_cycle')) {
                $table->string('billing_cycle', 30)->nullable()->after('billing_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'billing_day')) {
                $table->dropColumn('billing_day');
            }
            if (Schema::hasColumn('customers', 'billing_cycle')) {
                $table->dropColumn('billing_cycle');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'billing_cycle')) {
                $table->dropColumn('billing_cycle');
            }
        });
    }
};
