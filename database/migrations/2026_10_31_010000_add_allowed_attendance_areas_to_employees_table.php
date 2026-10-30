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
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'allowed_attendance_areas')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->json('allowed_attendance_areas')->nullable()->after('area_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'allowed_attendance_areas')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('allowed_attendance_areas');
            });
        }
    }
};

