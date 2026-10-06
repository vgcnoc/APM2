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
            $table->decimal('tax_ppn', 5, 2)->nullable()->after('status');
            $table->decimal('tax_bhp', 5, 2)->nullable()->after('tax_ppn');
            $table->decimal('tax_uso', 5, 2)->nullable()->after('tax_bhp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['tax_ppn', 'tax_bhp', 'tax_uso']);
        });
    }
};
