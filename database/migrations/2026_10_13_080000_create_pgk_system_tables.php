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
        Schema::create('pgk_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            
            // Info Pelapor & Saluran Komplain
            $table->string('complainant_name', 150)->nullable();
            $table->string('complainant_phone', 50)->nullable();
            $table->string('channel', 50)->default('whatsapp'); // whatsapp, telepon, datang_kantor, aplikasi, lainnya
            
            $table->string('category', 100);
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('reported_issue');
            $table->text('admin_notes')->nullable();
            
            // Status alur:
            // 1. admin_handling (Admin menerima komplain & penanganan awal)
            // 2. escalated_to_lead (Eskalasi ke Kepala Teknisi untuk jadwal)
            // 3. scheduled (Kepala teknisi menjadwalkan & menyiapkan material)
            // 4. in_progress (Teknisi sedang menangani di lapangan)
            // 5. resolved_by_admin (Selesai di tingkat admin jarak jauh)
            // 6. completed (Selesai dengan laporan penanganan teknisi)
            // 7. canceled (Dibatalkan)
            $table->enum('status', [
                'admin_handling',
                'escalated_to_lead',
                'scheduled',
                'in_progress',
                'resolved_by_admin',
                'completed',
                'canceled'
            ])->default('admin_handling');

            // Admin escalation details
            $table->timestamp('escalated_at')->nullable();
            $table->text('escalation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Lead Technician scheduling & notes
            $table->foreignId('lead_technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_date')->nullable();
            $table->string('scheduled_time', 50)->nullable();
            $table->text('lead_notes')->nullable();

            // Technician handling report
            $table->text('action_taken')->nullable();
            $table->string('optical_power_before', 50)->nullable();
            $table->string('optical_power_after', 50)->nullable();
            $table->string('start_time', 50)->nullable();
            $table->string('end_time', 50)->nullable();
            $table->string('photo_before', 255)->nullable();
            $table->string('photo_after', 255)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });

        // Pivot table for assigned technicians
        Schema::create('pgk_ticket_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pgk_ticket_id')->constrained('pgk_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // Material prepared by Lead Technician & used by Technician
        Schema::create('pgk_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pgk_ticket_id')->constrained('pgk_tickets')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('quantity_prepared', 10, 2)->default(0);
            $table->decimal('quantity_used', 10, 2)->default(0);
            $table->decimal('quantity_returned', 10, 2)->default(0);
            $table->string('unit', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pgk_materials');
        Schema::dropIfExists('pgk_ticket_user');
        Schema::dropIfExists('pgk_tickets');
    }
};
