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
        Schema::table('infrastructure_work_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('infrastructure_work_orders', 'photo_odp')) {
                $table->string('photo_odp')->nullable()->after('photo_opm');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('infrastructure_work_orders', function (Blueprint $table) {
            if (Schema::hasColumn('infrastructure_work_orders', 'photo_odp')) {
                $table->dropColumn('photo_odp');
            }
        });
    }
};
