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
        // 1. Tambah kolom pelacakan status pembayaran PSB ke tabel customers
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'psb_payment_status')) {
                // status: paid, unpaid, free
                $table->string('psb_payment_status', 20)->default('paid')->after('installation_fee');
            }
            if (!Schema::hasColumn('customers', 'psb_paid_at')) {
                $table->timestamp('psb_paid_at')->nullable()->after('psb_payment_status');
            }
            if (!Schema::hasColumn('customers', 'psb_payment_method')) {
                // cash, transfer, qris, dll
                $table->string('psb_payment_method', 50)->nullable()->after('psb_paid_at');
            }
            if (!Schema::hasColumn('customers', 'psb_received_by')) {
                $table->foreignId('psb_received_by')->nullable()->after('psb_payment_method')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('customers', 'psb_income_id')) {
                $table->unsignedBigInteger('psb_income_id')->nullable()->after('psb_received_by');
            }
            if (!Schema::hasColumn('customers', 'psb_notes')) {
                $table->text('psb_notes')->nullable()->after('psb_income_id');
            }
        });

        // 2. Tambah foreignId customer_id di tabel incomes jika belum ada
        if (Schema::hasTable('incomes') && !Schema::hasColumn('incomes', 'customer_id')) {
            Schema::table('incomes', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('amount')->constrained('customers')->nullOnDelete();
                $table->index('customer_id');
            });
        }

        // 3. Pastikan Kategori PSB ada di tabel income_categories
        if (Schema::hasTable('income_categories')) {
            $psbCategory = DB::table('income_categories')->where('code', 'PSB')->first();
            if (!$psbCategory) {
                DB::table('income_categories')->insert([
                    'code' => 'PSB',
                    'name' => 'Biaya Pasang Baru (PSB)',
                    'description' => 'Pemasukan dari biaya registrasi & pemasangan baru pelanggan internet',
                    'color' => 'emerald',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 4. Backfill data riwayat: untuk pelanggan yang sudah aktif dan memiliki installation_fee > 0
        // set psb_paid_at = activation_date jika belum terisi
        DB::table('customers')
            ->where('status', 'active')
            ->where('installation_fee', '>', 0)
            ->whereNull('psb_paid_at')
            ->update([
                'psb_payment_status' => 'paid',
                'psb_paid_at' => DB::raw('COALESCE(activation_date, NOW())'),
                'psb_payment_method' => 'cash',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('customers', 'psb_notes')) $cols[] = 'psb_notes';
            if (Schema::hasColumn('customers', 'psb_income_id')) $cols[] = 'psb_income_id';
            if (Schema::hasColumn('customers', 'psb_received_by')) $cols[] = 'psb_received_by';
            if (Schema::hasColumn('customers', 'psb_payment_method')) $cols[] = 'psb_payment_method';
            if (Schema::hasColumn('customers', 'psb_paid_at')) $cols[] = 'psb_paid_at';
            if (Schema::hasColumn('customers', 'psb_payment_status')) $cols[] = 'psb_payment_status';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        if (Schema::hasTable('incomes') && Schema::hasColumn('incomes', 'customer_id')) {
            Schema::table('incomes', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            });
        }
    }
};
