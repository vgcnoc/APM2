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
        if (!Schema::hasTable('field_duties')) {
            Schema::create('field_duties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('date');
                
                // Tipe Tugas & Tujuan
                $table->string('duty_type', 30)->default('reguler'); // 'reguler', 'non_reguler'
                $table->string('destination_location'); // e.g. "Cilandak Kaum", "Cigombong"
                $table->text('purpose')->nullable(); // e.g. "Market BC 1", "Refresh skill"
                
                // Mode Pengganti Absensi Reguler
                $table->string('attendance_mode', 30)->default('replace_both'); 
                // 'replace_both' (Masuk & Pulang), 'replace_in' (Masuk), 'replace_out' (Pulang), 'none' (Tambahan)

                // Jadwal Jam Terjadwal
                $table->time('scheduled_start_time')->nullable(); // e.g. "14:00:00"
                $table->time('scheduled_end_time')->nullable();   // e.g. "15:00:00"
                $table->integer('scheduled_duration_minutes')->default(60);
                
                // Waktu & Foto Check-In / Check-Out Lapangan
                $table->time('check_in_time')->nullable();
                $table->time('check_out_time')->nullable();
                $table->string('check_in_photo')->nullable();
                $table->string('check_out_photo')->nullable();
                
                // Koordinat Geolocation
                $table->decimal('lat_in', 10, 8)->nullable();
                $table->decimal('lng_in', 11, 8)->nullable();
                $table->decimal('lat_out', 10, 8)->nullable();
                $table->decimal('lng_out', 11, 8)->nullable();
                $table->string('location_name_in')->nullable();
                $table->string('location_name_out')->nullable();
                
                // Status Pelaksanaan & Approval
                $table->string('status', 30)->default('active'); // 'active' (Tugas Aktif), 'completed' (Selesai), 'cancelled' (Batal)
                $table->string('approval_status', 30)->default('auto'); // 'auto', 'approved', 'pending', 'rejected'
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                
                // Uang Tugas / Allowance
                $table->decimal('duty_allowance_amount', 15, 2)->default(0);
                $table->boolean('is_allowance_synced')->default(false);
                $table->boolean('is_regular_attendance_synced')->default(false);
                
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('date');
                $table->index('status');
                $table->index('duty_type');
            });
        }

        // Seed Sample Data seperti yang ada di screenshot pengguna jika tabel masih kosong
        if (Schema::hasTable('field_duties') && Schema::hasTable('employees')) {
            $count = DB::table('field_duties')->count();
            if ($count === 0) {
                $hikmah = DB::table('employees')->where('name', 'like', '%HIKMAH%')->first();
                $dea = DB::table('employees')->where('name', 'like', '%Dea%')->first();

                if ($hikmah) {
                    DB::table('field_duties')->insert([
                        'employee_id' => $hikmah->id,
                        'date' => '2026-10-04',
                        'duty_type' => 'reguler',
                        'destination_location' => 'Cilandak Kaum',
                        'purpose' => 'Market BC 1',
                        'attendance_mode' => 'replace_in',
                        'scheduled_start_time' => '14:00:00',
                        'scheduled_end_time' => '15:00:00',
                        'scheduled_duration_minutes' => 60,
                        'status' => 'active',
                        'approval_status' => 'auto',
                        'duty_allowance_amount' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($dea) {
                    DB::table('field_duties')->insert([
                        'employee_id' => $dea->id,
                        'date' => '2026-10-03',
                        'duty_type' => 'reguler',
                        'destination_location' => 'Cigombong',
                        'purpose' => 'Refresh skill',
                        'attendance_mode' => 'replace_in',
                        'scheduled_start_time' => '14:20:00',
                        'scheduled_end_time' => '15:10:00',
                        'scheduled_duration_minutes' => 50,
                        'status' => 'active',
                        'approval_status' => 'auto',
                        'duty_allowance_amount' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('field_duties')->insert([
                        'employee_id' => $dea->id,
                        'date' => '2026-09-27',
                        'duty_type' => 'reguler',
                        'destination_location' => 'Cilandak',
                        'purpose' => 'Market BC 1',
                        'attendance_mode' => 'replace_in',
                        'scheduled_start_time' => '14:30:00',
                        'scheduled_end_time' => '15:30:00',
                        'scheduled_duration_minutes' => 60,
                        'status' => 'active',
                        'approval_status' => 'auto',
                        'duty_allowance_amount' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_duties');
    }
};
