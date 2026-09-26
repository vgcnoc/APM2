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
        Schema::table('materials', function (Blueprint $table) {
            $table->string('supplier')->nullable()->after('name');
            $table->decimal('meter_per_roll', 10, 2)->nullable()->after('unit');
            $table->decimal('total_rolls', 10, 2)->nullable()->after('meter_per_roll');
            $table->decimal('selling_price', 15, 2)->default(0)->after('price_per_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['supplier', 'meter_per_roll', 'total_rolls', 'selling_price']);
        });
    }
};
