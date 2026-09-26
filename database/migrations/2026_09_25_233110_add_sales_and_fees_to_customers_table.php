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
            $table->decimal('base_amount', 15, 2)->default(0)->after('package_id');
            $table->decimal('installation_fee', 15, 2)->default(0)->after('base_amount');
            $table->foreignId('sales_id')->nullable()->after('installation_fee')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['sales_id']);
            $table->dropColumn(['base_amount', 'installation_fee', 'sales_id']);
        });
    }
};
