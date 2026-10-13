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
        // 1. Tabel Roster Patterns (Template Pola Shift)
        Schema::create('roster_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('pattern_data'); // array of shift_id or 'OFF' e.g. [1, 1, 1, 1, 1, 1, "OFF"]
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Work Schedules (Jadwal Kerja Karyawan per Tanggal)
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->date('date');
            $table->string('status', 20)->default('work'); // 'work', 'off', 'leave', 'sick'
            $table->string('notes')->nullable();
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
        Schema::dropIfExists('work_schedules');
        Schema::dropIfExists('roster_patterns');
    }
};
