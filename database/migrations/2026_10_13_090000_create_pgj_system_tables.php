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
        // 1. Tabel Utama Tiket Penanganan Gangguan Jalur (PGJ)
        Schema::create('pgj_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->enum('segment_type', ['backbone', 'odc', 'odp', 'distribution', 'other'])->default('odc');
            
            // Relasi infrastruktur & wilayah
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('odc_id')->nullable()->constrained('odcs')->nullOnDelete();
            $table->foreignId('odp_id')->nullable()->constrained('odps')->nullOnDelete();
            $table->integer('affected_customers_count')->default(0);

            // Lokasi & Pengukuran Fisik
            $table->string('location_name')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('otdr_distance')->nullable(); // e.g. "1.32 km dari ODC"

            // Sumber temuan & Rincian Gangguan
            $table->string('source_report')->default('Monitoring NMS'); // NMS, Patroli, Warga, dll
            $table->string('category'); // e.g. Kabel FO Putus, Bending Redaman Drop, Splitter Rusak, Tiang Roboh
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('critical');
            $table->enum('status', [
                'admin_handling',      // 1. Ditangani / Temuan Admin
                'escalated_to_lead',   // Dieskalasi ke Kepala Teknisi
                'scheduled',           // 2. Dijadwalkan oleh Kepala Teknisi
                'completed'            // 3. Selesai oleh Teknisi
            ])->default('admin_handling');

            $table->text('reported_issue');
            $table->text('admin_notes')->nullable();
            $table->text('escalation_reason')->nullable();

            // Penjadwalan & Catatan Ka. Teknisi
            $table->date('scheduled_date')->nullable();
            $table->string('scheduled_time')->nullable();
            $table->text('lead_notes')->nullable();

            // Laporan Hasil Penanganan Teknisi
            $table->text('action_taken')->nullable();
            $table->string('core_details')->nullable(); // e.g. "Core 1-12 tube biru ODC-02"
            $table->string('optical_loss_before')->nullable(); // e.g. "LOS / Cut"
            $table->string('optical_loss_after')->nullable();  // e.g. "0.05 dB / -18 dBm"
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('photo_before')->nullable();
            $table->string('photo_after')->nullable();
            $table->text('resolution_notes')->nullable();

            // Pihak yang terlibat
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('lead_technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });

        // 2. Pivot Table Penugasan Teknisi Pelaksana Jalur
        Schema::create('pgj_ticket_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pgj_ticket_id')->constrained('pgj_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // 3. Tabel Alokasi & Rekonsiliasi Material Gudang Jalur
        Schema::create('pgj_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pgj_ticket_id')->constrained('pgj_tickets')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('quantity_prepared', 10, 2)->default(0);
            $table->decimal('quantity_used', 10, 2)->default(0);
            $table->decimal('quantity_returned', 10, 2)->default(0);
            $table->string('unit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pgj_materials');
        Schema::dropIfExists('pgj_ticket_user');
        Schema::dropIfExists('pgj_tickets');
    }
};
