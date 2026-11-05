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
        // 1. Master Tabel Invoice Proyek & Kerjasama B2B
        if (!Schema::hasTable('project_invoices')) {
            Schema::create('project_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number', 50)->unique();
                $table->string('client_name'); // Nama Perusahaan / Instansi / Klien
                $table->string('client_pic')->nullable(); // Nama Penanggung Jawab / PIC
                $table->string('client_phone', 50)->nullable(); // WhatsApp / Telepon
                $table->string('client_email')->nullable();
                $table->text('client_address')->nullable();
                $table->string('client_npwp', 50)->nullable();
                $table->string('po_spk_number', 100)->nullable(); // No PO / Surat Perintah Kerja / Kontrak
                $table->string('project_title'); // Judul Proyek / Pengadaan
                $table->string('project_location')->nullable();
                
                $table->date('issue_date'); // Tanggal Terbit
                $table->date('due_date')->nullable(); // Tanggal Jatuh Tempo
                
                $table->string('payment_scheme', 30)->default('full'); // full, termin_dp, termin_progress, termin_final
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('tax_ppn_percent', 5, 2)->default(0);
                $table->decimal('tax_ppn_amount', 15, 2)->default(0);
                $table->decimal('tax_pph23_percent', 5, 2)->default(0);
                $table->decimal('tax_pph23_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->decimal('remaining_amount', 15, 2)->default(0);
                
                $table->string('status', 30)->default('unpaid'); // draft, unpaid, partial, paid, cancelled
                $table->string('payment_method', 50)->nullable(); // transfer, cash, giro, dll
                $table->string('payment_bank_info', 255)->nullable(); // Bank tujuan pembayaran
                $table->dateTime('paid_at')->nullable();
                $table->text('payment_notes')->nullable(); // No referensi transfer / catatan bayar
                
                $table->text('notes')->nullable(); // Syarat, ketentuan & catatan khusus invoice
                $table->foreignId('income_id')->nullable()->constrained('incomes')->nullOnDelete(); // Terhubung ke Buku Kas Umum
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('invoice_number');
                $table->index('status');
                $table->index('issue_date');
                $table->index('due_date');
                $table->index('created_by');
                $table->index('income_id');
            });
        }

        // 2. Tabel Rincian Item Pekerjaan / Jasa / Material Proyek
        if (!Schema::hasTable('project_invoice_items')) {
            Schema::create('project_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_invoice_id')->constrained('project_invoices')->cascadeOnDelete();
                $table->text('description'); // Uraian Pekerjaan / Material / Jasa
                $table->decimal('qty', 10, 2)->default(1);
                $table->string('unit', 50)->default('Unit'); // Meter, Titik, Core, Roll, Unit, Paket, Hari, Set, dll
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('project_invoice_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_invoice_items');
        Schema::dropIfExists('project_invoices');
    }
};

