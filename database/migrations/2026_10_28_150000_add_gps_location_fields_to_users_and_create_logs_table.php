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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_latitude')) {
                $table->decimal('last_latitude', 10, 8)->nullable()->after('area_id');
            }
            if (!Schema::hasColumn('users', 'last_longitude')) {
                $table->decimal('last_longitude', 11, 8)->nullable()->after('last_latitude');
            }
            if (!Schema::hasColumn('users', 'last_location_name')) {
                $table->text('last_location_name')->nullable()->after('last_longitude');
            }
            if (!Schema::hasColumn('users', 'last_location_accuracy')) {
                $table->decimal('last_location_accuracy', 8, 2)->nullable()->after('last_location_name');
            }
            if (!Schema::hasColumn('users', 'last_gps_updated_at')) {
                $table->timestamp('last_gps_updated_at')->nullable()->after('last_location_accuracy');
            }
            if (!Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('last_gps_updated_at');
            }
            if (!Schema::hasColumn('users', 'is_gps_enabled')) {
                $table->boolean('is_gps_enabled')->default(false)->after('last_seen_at');
            }
        });

        if (!Schema::hasTable('employee_location_logs')) {
            Schema::create('employee_location_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->decimal('accuracy', 8, 2)->nullable();
                $table->string('location_name', 500)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_location_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_latitude',
                'last_longitude',
                'last_location_name',
                'last_location_accuracy',
                'last_gps_updated_at',
                'last_seen_at',
                'is_gps_enabled',
            ]);
        });
    }
};

