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
        // 1. Tabel Kategori Pemasukan (Income Categories)
        if (!Schema::hasTable('income_categories')) {
            Schema::create('income_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('color', 30)->default('emerald');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Auto-seed default categories
            $defaults = [
                ['code' => 'JSA', 'name' => 'Jasa Setting & Instalasi Khusus', 'description' => 'Penarikan kabel FO khusus, setting router/mikrotik, instalasi CCTV & hotspot', 'color' => 'emerald'],
                ['code' => 'BRG', 'name' => 'Penjualan Perangkat & Material', 'description' => 'Penjualan ONT/Router bekas atau baru, kabel sisa, patchcord, adaptor', 'color' => 'blue'],
                ['code' => 'SEW', 'name' => 'Sewa Tiang & Infrastruktur FO', 'description' => 'Sewa tiang bersama, sewa core fiber optik, colocation rack', 'color' => 'indigo'],
                ['code' => 'VCH', 'name' => 'Voucher Grosir & Offline', 'description' => 'Penjualan voucher fisik titip toko / warung / mitra reseller', 'color' => 'purple'],
                ['code' => 'KOM', 'name' => 'Komisi & Fee Mitra', 'description' => 'Komisi mitra, cashback vendor/supplier, bagi hasil kemitraan', 'color' => 'amber'],
                ['code' => 'REF', 'name' => 'Pengembalian / Refund / Kasbon', 'description' => 'Pengembalian dana vendor, setoran pelunasan kasbon karyawan/teknisi', 'color' => 'cyan'],
                ['code' => 'INV', 'name' => 'Modal Masuk & Setoran Kas', 'description' => 'Suntikan modal owner, setoran kas awal, investasi', 'color' => 'teal'],
                ['code' => 'OTH', 'name' => 'Pemasukan Lain-lain', 'description' => 'Pemasukan operasional dan non-operasional lainnya di luar tagihan', 'color' => 'gray'],
            ];

            foreach ($defaults as $item) {
                DB::table('income_categories')->insert(array_merge($item, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 2. Tabel Pemasukan Manual / Non-Tagihan (Incomes)
        if (!Schema::hasTable('incomes')) {
            Schema::create('incomes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('amount');
                $table->foreignId('income_category_id')->nullable()->constrained('income_categories')->nullOnDelete();
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
                $table->string('area')->nullable();
                $table->string('source')->nullable(); // Nama pihak / penyetor / mitra / klien
                $table->string('description', 500);
                $table->date('income_date');
                $table->string('payment_method', 50)->default('transfer'); // transfer, cash, qris, dll
                $table->string('reference_no', 100)->nullable(); // Nomor bukti bayar / transfer / kwitansi
                $table->string('attachment')->nullable(); // Path bukti foto
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                // Index untuk performa query ledger & filter
                $table->index('income_date');
                $table->index('area_id');
                $table->index('income_category_id');
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('income_categories');
    }
};

