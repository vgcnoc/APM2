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
            $table->decimal('pcs_per_pack', 10, 2)->nullable()->after('total_rolls');
            $table->decimal('total_packs', 10, 2)->nullable()->after('pcs_per_pack');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['pcs_per_pack', 'total_packs']);
        });
    }
};
