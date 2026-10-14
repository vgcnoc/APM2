<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Utama Work Order Pembangunan ODC & ODP
        if (!Schema::hasTable('infrastructure_work_orders')) {
            Schema::create('infrastructure_work_orders', function (Blueprint $table) {
                $table->id();
                $table->string('wo_number')->unique()->index();
                $table->string('type'); // 'odc_deployment', 'odp_deployment'
                $table->string('target_name');
                $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
                
                // Relasi Jaringan Induk
                $table->foreignId('olt_id')->nullable()->constrained('olts')->nullOnDelete();
                $table->unsignedInteger('pon_port')->nullable();
                $table->foreignId('odc_id')->nullable()->constrained('odcs')->nullOnDelete();
                $table->unsignedInteger('odc_splitter_port')->nullable();
                
                // Spesifikasi Perangkat
                $table->unsignedInteger('capacity')->default(8); // misal 8, 16, 24, 48
                $table->string('splitter_ratio')->nullable(); // misal '1:4', '1:8', '1:16', 'Split 2'
                $table->text('location')->nullable();
                $table->decimal('planned_latitude', 10, 7)->nullable();
                $table->decimal('planned_longitude', 10, 7)->nullable();
                $table->decimal('actual_latitude', 10, 7)->nullable();
                $table->decimal('actual_longitude', 10, 7)->nullable();
                
                // Penugasan
                $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->date('scheduled_date');
                $table->string('status')->default('pending_material'); 
                // pending_material, material_ready, in_progress, under_review, completed, rejected
                
                // Pelaksanaan Lapangan & Penarikan Kabel
                $table->string('start_point')->nullable();
                $table->string('end_point')->nullable();
                $table->unsignedInteger('cable_meter_start')->nullable();
                $table->unsignedInteger('cable_meter_end')->nullable();
                $table->unsignedInteger('cable_length_used')->nullable(); // meteran aktual
                
                // Bukti Foto Lapangan
                $table->string('photo_pole')->nullable();
                $table->string('photo_splicing')->nullable();
                $table->string('photo_opm')->nullable();
                
                $table->text('notes')->nullable();
                $table->text('rejection_reason')->nullable();
                
                // Komisioning & Approval
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('created_odc_id')->nullable()->constrained('odcs')->nullOnDelete();
                $table->foreignId('created_odp_id')->nullable()->constrained('odps')->nullOnDelete();
                
                $table->timestamps();
            });
        }

        // 2. Tabel Item Material (Auto-BOM & Rekonsiliasi Pengeluaran/Retur)
        if (!Schema::hasTable('infrastructure_work_order_materials')) {
            Schema::create('infrastructure_work_order_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_order_id')->constrained('infrastructure_work_orders')->cascadeOnDelete();
                $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
                $table->decimal('requested_qty', 10, 2);
                $table->decimal('used_qty', 10, 2)->nullable();
                $table->decimal('returned_qty', 10, 2)->nullable();
                $table->string('unit')->default('pcs');
                $table->string('status')->default('allocated'); // allocated, taken, reconciled
                $table->timestamps();
            });
        }

        // 3. Tabel Uji Redaman Optik (Optical Power Meter QC)
        if (!Schema::hasTable('infrastructure_work_order_optical_checks')) {
            Schema::create('infrastructure_work_order_optical_checks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_order_id')->constrained('infrastructure_work_orders')->cascadeOnDelete();
                $table->unsignedInteger('port_number');
                $table->string('wavelength')->default('1490nm');
                $table->decimal('rx_power_dbm', 8, 2);
                $table->string('status')->default('pass'); // pass, warning, fail
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastructure_work_order_optical_checks');
        Schema::dropIfExists('infrastructure_work_order_materials');
        Schema::dropIfExists('infrastructure_work_orders');
    }
};
