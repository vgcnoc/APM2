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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('work_schedule_id')->nullable()->constrained('work_schedules')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->date('date');
            
            // Jam Masuk & Pulang
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            
            // Foto Selfie Masuk & Pulang
            $table->string('photo_in')->nullable();
            $table->string('photo_out')->nullable();
            
            // Koordinat Geolocation GPS
            $table->decimal('lat_in', 10, 8)->nullable();
            $table->decimal('lng_in', 11, 8)->nullable();
            $table->decimal('lat_out', 10, 8)->nullable();
            $table->decimal('lng_out', 11, 8)->nullable();
            
            // Jarak ke Area dalam meter
            $table->integer('distance_in')->nullable();
            $table->integer('distance_out')->nullable();
            
            // Status & Keterlambatan
            $table->string('status', 30)->default('present'); // present, late, early_leave, absent
            $table->integer('late_minutes')->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
