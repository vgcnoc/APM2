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
        // 1. Tabel Utama Pemeliharaan & Perapihan Jalur (Tanpa Tiket Gangguan PGS/PGK/PGJ)
        Schema::create('network_maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('maintenance_number')->unique(); // e.g. MNT-202610-0001
            
            // Kategori Kegiatan Pemeliharaan & Perapihan
            $table->enum('category', [
                'perapihan_jalur',        // Perapihan & Penataan Kabel / Tiang
                'pengecekan_odp',         // Pengecekan / Inspeksi Fisik & Redaman ODP
                'pengecekan_odc',         // Pengecekan / Inspeksi Splitter & Feeder ODC
                'pengecekan_jalur_utama', // Pengecekan / Patroli Jalur Utama (Backbone / Feeder)
                'pemeliharaan_rutin'      // Pemeliharaan Rutin Lainnya
            ])->default('perapihan_jalur');

            $table->string('title'); // e.g. "Perapihan Kabel Kendur dan Penataan Slack di Depan Pasar"
            
            // Relasi Aset & Wilayah
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('odp_id')->nullable()->constrained('odps')->nullOnDelete();
            $table->foreignId('odc_id')->nullable()->constrained('odcs')->nullOnDelete();
            
            $table->string('location_name')->nullable(); // Alamat / Patokan Segmen Rute Lapangan
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Status & Prioritas
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', [
                'scheduled',    // Terjadwal / Belum Mulai
                'in_progress',  // Sedang Dikerjakan
                'completed',    // Selesai
                'rescheduled',  // Dijadwalkan Ulang
                'cancelled'     // Dibatalkan
            ])->default('scheduled');

            // Penjadwalan & Waktu Pelaksanaan
            $table->date('scheduled_date');
            $table->string('scheduled_time')->nullable(); // e.g. "09:00"
            $table->string('start_time')->nullable();     // e.g. "09:15"
            $table->string('end_time')->nullable();       // e.g. "11:30"

            // Detail Rencana & Hasil Pengerjaan
            $table->text('description')->nullable();     // Rencana kerja / instruksi pekerjaan
            $table->text('action_taken')->nullable();    // Tindakan penyelesaian / hasil pengecekan
            
            // Pengukuran Optik (OPM / Redaman dBm)
            $table->string('optical_power_before')->nullable(); // e.g. "-23.4 dBm"
            $table->string('optical_power_after')->nullable();  // e.g. "-17.8 dBm"
            
            // Dokumentasi Foto Lapangan
            $table->string('photo_before')->nullable();
            $table->string('photo_after')->nullable();

            // Pihak yang Terlibat
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indeks untuk Performa Query
            $table->index(['scheduled_date', 'status']);
            $table->index(['area_id', 'scheduled_date']);
            $table->index('category');
        });

        // 2. Pivot Table Penugasan Teknisi Maintenance
        Schema::create('network_maintenance_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_maintenance_id')->constrained('network_maintenances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('technician'); // technician, lead, assistant
            $table->timestamps();

            $table->unique(['network_maintenance_id', 'user_id'], 'net_maint_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_maintenance_user');
        Schema::dropIfExists('network_maintenances');
    }
};

