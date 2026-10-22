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
        // 1. Tabel Material yang Terpasang di Pelanggan (Customer Installed Materials)
        if (!Schema::hasTable('customer_installed_materials')) {
            Schema::create('customer_installed_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
                $table->string('item_name', 255);
                $table->decimal('quantity', 10, 2)->default(1);
                $table->string('unit', 50)->default('pcs');
                $table->string('serial_number', 100)->nullable();
                $table->dateTime('installed_at')->nullable();
                $table->string('installation_source', 50)->default('manual_initial'); // psb_installation, pgs_maintenance, pgk_maintenance, manual_initial
                $table->string('status', 50)->default('installed'); // installed, partially_replaced, fully_replaced, dismantled
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('customer_id');
                $table->index('status');
            });
        }

        // 2. Tabel Serah Terima Material Bekas / Bongkaran dari Pelanggan ke Gudang / Ka. Teknisi
        if (!Schema::hasTable('customer_material_dismantles')) {
            Schema::create('customer_material_dismantles', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_type', 30)->default('pgs'); // pgs, pgk, psb, pgj
                $table->unsignedBigInteger('ticket_id')->nullable();
                $table->string('ticket_number', 50)->nullable();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->unsignedBigInteger('customer_installed_material_id')->nullable();
                $table->foreign('customer_installed_material_id', 'fk_cmd_inst_mat')
                      ->references('id')
                      ->on('customer_installed_materials')
                      ->nullOnDelete();
                $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
                $table->string('item_name', 255);
                $table->string('replacement_type', 30)->default('full'); // full (seluruhnya), partial (sebagian), dismantle_only
                $table->decimal('quantity', 10, 2)->default(1);
                $table->string('unit', 50)->default('pcs');
                $table->string('condition', 50)->default('rusak_scrap'); // rusak_scrap, normal_reusable, perlu_cek
                $table->string('damage_reason', 255)->nullable();
                $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('technician_name', 150)->nullable();
                
                // Status Serah Terima:
                // pending_handover: teknisi membawa barang dari lapangan ke kantor/gudang
                // received_warehouse: diterima dan diverifikasi oleh petugas gudang
                // received_lead: diterima dan diverifikasi oleh kepala teknisi
                // rejected: ditolak atau selisih fisik
                $table->string('handover_status', 50)->default('pending_handover');
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('received_at')->nullable();
                $table->text('received_notes')->nullable();
                
                // Disposisi akhir:
                // scrap: masuk gudang afkir/scrap (tidak menambah stok aktif)
                // restocked: layak pakai, otomatis masuk stok gudang
                // reconditioned: dalam proses perbaikan/servis lab
                $table->string('disposition', 50)->nullable(); 

                $table->unsignedBigInteger('material_transaction_id')->nullable();
                $table->foreign('material_transaction_id', 'fk_cmd_trx')
                      ->references('id')
                      ->on('material_transactions')
                      ->nullOnDelete();
                $table->timestamps();

                $table->index('customer_id');
                $table->index('handover_status');
                $table->index(['ticket_type', 'ticket_id']);
                $table->index('ticket_number');
            });
        }

        // 3. Tambahkan kolom incident_coordinates ke pgs_tickets dan pgk_tickets
        if (Schema::hasTable('pgs_tickets') && !Schema::hasColumn('pgs_tickets', 'incident_coordinates')) {
            Schema::table('pgs_tickets', function (Blueprint $table) {
                $table->json('incident_coordinates')->nullable()->after('resolution_notes');
            });
        }

        if (Schema::hasTable('pgk_tickets') && !Schema::hasColumn('pgk_tickets', 'incident_coordinates')) {
            Schema::table('pgk_tickets', function (Blueprint $table) {
                $table->json('incident_coordinates')->nullable()->after('resolution_notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pgk_tickets') && Schema::hasColumn('pgk_tickets', 'incident_coordinates')) {
            Schema::table('pgk_tickets', function (Blueprint $table) {
                $table->dropColumn('incident_coordinates');
            });
        }

        if (Schema::hasTable('pgs_tickets') && Schema::hasColumn('pgs_tickets', 'incident_coordinates')) {
            Schema::table('pgs_tickets', function (Blueprint $table) {
                $table->dropColumn('incident_coordinates');
            });
        }

        Schema::dropIfExists('customer_material_dismantles');
        Schema::dropIfExists('customer_installed_materials');
    }
};
