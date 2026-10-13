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
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('attendances', 'attendance_type')) {
                    $table->string('attendance_type', 30)->default('wfo')->after('shift_id');
                }
                if (!Schema::hasColumn('attendances', 'duty_location')) {
                    $table->string('duty_location')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('attendances', 'is_verified')) {
                    $table->boolean('is_verified')->default(true)->after('duty_location');
                }
                if (!Schema::hasColumn('attendances', 'is_synced_to_payroll')) {
                    $table->boolean('is_synced_to_payroll')->default(false)->after('is_verified');
                }
            });
        }

        if (Schema::hasTable('areas')) {
            Schema::table('areas', function (Blueprint $table) {
                if (!Schema::hasColumn('areas', 'radius_meters')) {
                    $table->integer('radius_meters')->default(200)->after('longitude');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $columns = ['attendance_type', 'duty_location', 'is_verified', 'is_synced_to_payroll'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('attendances', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('areas')) {
            Schema::table('areas', function (Blueprint $table) {
                if (Schema::hasColumn('areas', 'radius_meters')) {
                    $table->dropColumn('radius_meters');
                }
            });
        }
    }
};
