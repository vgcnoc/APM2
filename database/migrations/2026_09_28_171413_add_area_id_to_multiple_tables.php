<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'area_id')) {
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            }
        });

        Schema::table('onts', function (Blueprint $table) {
            if (!Schema::hasColumn('onts', 'area_id')) {
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            }
        });

        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'area_id')) {
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            }
        });

        Schema::table('technician_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('technician_schedules', 'area_id')) {
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            }
        });

        // note: material_transactions already has 'area' (string) column which they use in populate_initial_stock. 
        // to be consistent, we also add area_id here
        Schema::table('material_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('material_transactions', 'area_id')) {
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'area_id')) {
                $table->dropForeign(['area_id']);
                $table->dropColumn('area_id');
            }
        });

        Schema::table('onts', function (Blueprint $table) {
            if (Schema::hasColumn('onts', 'area_id')) {
                $table->dropForeign(['area_id']);
                $table->dropColumn('area_id');
            }
        });

        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'area_id')) {
                $table->dropForeign(['area_id']);
                $table->dropColumn('area_id');
            }
        });

        Schema::table('technician_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('technician_schedules', 'area_id')) {
                $table->dropForeign(['area_id']);
                $table->dropColumn('area_id');
            }
        });

        Schema::table('material_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('material_transactions', 'area_id')) {
                $table->dropForeign(['area_id']);
                $table->dropColumn('area_id');
            }
        });
    }
};
