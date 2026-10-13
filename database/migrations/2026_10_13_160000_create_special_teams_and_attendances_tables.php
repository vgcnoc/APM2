<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Table: special_teams
        if (!Schema::hasTable('special_teams')) {
            Schema::create('special_teams', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g. "GRUP TIM KHUSUS", "TIM SERBU"
                $table->string('code')->nullable(); // e.g. "TK-01", "TS-01"
                $table->time('start_time')->default('08:30:00');
                $table->time('end_time')->default('18:00:00');
                $table->integer('tolerance_minutes')->default(15);
                $table->boolean('without_radius')->default(true);
                $table->decimal('late_deduction_amount', 15, 2)->default(10000);
                $table->decimal('no_out_deduction_amount', 15, 2)->default(10000);
                $table->decimal('alpha_deduction_amount', 15, 2)->default(35000);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Table: special_team_members
        if (!Schema::hasTable('special_team_members')) {
            Schema::create('special_team_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('special_team_id')->constrained('special_teams')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('role')->default('Anggota'); // e.g. "Ketua Tim", "Anggota", "Teknisi"
                $table->boolean('is_leader')->default(false);
                $table->timestamps();

                $table->unique(['special_team_id', 'employee_id']);
            });
        }

        // 3. Table: special_attendances
        if (!Schema::hasTable('special_attendances')) {
            Schema::create('special_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('special_team_id')->constrained('special_teams')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('date');
                
                // Clock In & Clock Out Times
                $table->time('clock_in')->nullable();
                $table->time('clock_out')->nullable();
                
                // Selfie Photos
                $table->string('photo_in')->nullable();
                $table->string('photo_out')->nullable();
                
                // Coordinates
                $table->decimal('lat_in', 10, 8)->nullable();
                $table->decimal('lng_in', 11, 8)->nullable();
                $table->decimal('lat_out', 10, 8)->nullable();
                $table->decimal('lng_out', 11, 8)->nullable();
                $table->string('location_in')->nullable();
                $table->string('location_out')->nullable();
                
                // Status, Late & Early leave
                $table->string('status', 30)->default('present'); // present, late, early_leave, absent
                $table->integer('late_minutes')->default(0);
                $table->integer('early_leave_minutes')->default(0);
                $table->text('notes')->nullable();
                $table->boolean('is_synced_to_payroll')->default(false);
                
                $table->timestamps();

                $table->unique(['special_team_id', 'employee_id', 'date']);
                $table->index('date');
                $table->index('status');
            });
        }

        // Seed default groups
        if (Schema::hasTable('special_teams')) {
            $count = DB::table('special_teams')->count();
            if ($count === 0) {
                $teamKhususId = DB::table('special_teams')->insertGetId([
                    'name' => 'GRUP TIM KHUSUS',
                    'code' => 'TK-01',
                    'start_time' => '08:30:00',
                    'end_time' => '18:00:00',
                    'tolerance_minutes' => 15,
                    'without_radius' => true,
                    'late_deduction_amount' => 10000,
                    'no_out_deduction_amount' => 10000,
                    'alpha_deduction_amount' => 35000,
                    'description' => 'Grup absensi tim khusus operasional dan lapangan tanpa batasan radius kantor.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $teamSerbuId = DB::table('special_teams')->insertGetId([
                    'name' => 'TIM SERBU',
                    'code' => 'TS-01',
                    'start_time' => '08:30:00',
                    'end_time' => '18:00:00',
                    'tolerance_minutes' => 15,
                    'without_radius' => true,
                    'late_deduction_amount' => 10000,
                    'no_out_deduction_amount' => 10000,
                    'alpha_deduction_amount' => 35000,
                    'description' => 'Grup absensi tim serbu maintenance dan insidental tanpa batasan radius kantor.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Auto enroll employees to GRUP TIM KHUSUS
                if (Schema::hasTable('employees')) {
                    $employeeIds = DB::table('employees')->pluck('id');
                    foreach ($employeeIds as $empId) {
                        DB::table('special_team_members')->insertOrIgnore([
                            'special_team_id' => $teamKhususId,
                            'employee_id' => $empId,
                            'role' => 'Anggota',
                            'is_leader' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_attendances');
        Schema::dropIfExists('special_team_members');
        Schema::dropIfExists('special_teams');
    }
};
