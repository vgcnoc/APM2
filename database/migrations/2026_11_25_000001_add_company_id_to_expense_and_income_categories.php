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
        // 1. Tambah company_id ke expense_categories
        if (Schema::hasTable('expense_categories') && !Schema::hasColumn('expense_categories', 'company_id')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
            });

            // Pastikan seluruh kategori yang sudah ada milik Holding (company_id = 1)
            DB::table('expense_categories')->whereNull('company_id')->update(['company_id' => 1]);

            // Duplikasi / seed kategori standar untuk setiap Mitra yang sudah terdaftar
            try {
                $mitraCompanies = DB::table('companies')
                    ->where(function ($q) {
                        $q->where('id', '>', 1)->orWhere('is_holding', false);
                    })
                    ->pluck('id');

                $defaultExpenseCategories = [
                    ['code' => 'OPR', 'name' => 'Operasional Lapangan', 'description' => 'BBM, konsumsi teknisi, transportasi, parkir, tol', 'color' => 'amber'],
                    ['code' => 'MNT', 'name' => 'Maintenance Jaringan', 'description' => 'Perbaikan FO cut, penggantian adaptor/dropcore/fast connector', 'color' => 'rose'],
                    ['code' => 'BW',  'name' => 'Bandwidth & Upstream', 'description' => 'Biaya langganan internet upstream, IP Transit, colocation server', 'color' => 'indigo'],
                    ['code' => 'PLN', 'name' => 'Listrik & Utilitas POP', 'description' => 'Token PLN OLT, genset, baterai UPS, sewa tiang/tempat POP', 'color' => 'emerald'],
                    ['code' => 'TLS', 'name' => 'Peralatan & Perkakas Kerja', 'description' => 'Splicer, OTDR, tang potong, tangga, alat safety, stripper', 'color' => 'blue'],
                    ['code' => 'MKT', 'name' => 'Marketing & Promosi', 'description' => 'Cetak brosur, banner, spanduk, merchandise, iklan medsos', 'color' => 'purple'],
                    ['code' => 'ADM', 'name' => 'Administrasi & Kantor', 'description' => 'Kertas invoice, printer, ATK, operasional kantor', 'color' => 'cyan'],
                    ['code' => 'OTH', 'name' => 'Pengeluaran Lainnya', 'description' => 'Pengeluaran umum lain yang tidak termasuk kategori di atas', 'color' => 'gray'],
                ];

                foreach ($mitraCompanies as $mitraId) {
                    $hasCat = DB::table('expense_categories')->where('company_id', $mitraId)->exists();
                    if (!$hasCat) {
                        foreach ($defaultExpenseCategories as $item) {
                            DB::table('expense_categories')->insert(array_merge($item, [
                                'company_id' => $mitraId,
                                'is_active' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]));
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 2. Tambah company_id ke income_categories
        if (Schema::hasTable('income_categories') && !Schema::hasColumn('income_categories', 'company_id')) {
            Schema::table('income_categories', function (Blueprint $table) {
                $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
            });

            // Pastikan seluruh kategori yang sudah ada milik Holding (company_id = 1)
            DB::table('income_categories')->whereNull('company_id')->update(['company_id' => 1]);

            // Duplikasi / seed kategori standar untuk setiap Mitra yang sudah terdaftar
            try {
                $mitraCompanies = DB::table('companies')
                    ->where(function ($q) {
                        $q->where('id', '>', 1)->orWhere('is_holding', false);
                    })
                    ->pluck('id');

                $defaultIncomeCategories = [
                    ['code' => 'JSA', 'name' => 'Jasa Setting & Instalasi Khusus', 'description' => 'Penarikan kabel FO khusus, setting router/mikrotik, instalasi CCTV & hotspot', 'color' => 'emerald'],
                    ['code' => 'BRG', 'name' => 'Penjualan Perangkat & Material', 'description' => 'Penjualan ONT/Router bekas atau baru, kabel sisa, patchcord, adaptor', 'color' => 'blue'],
                    ['code' => 'SEW', 'name' => 'Sewa Tiang & Infrastruktur FO', 'description' => 'Sewa tiang bersama, sewa core fiber optik, colocation rack', 'color' => 'indigo'],
                    ['code' => 'VCH', 'name' => 'Voucher Grosir & Offline', 'description' => 'Penjualan voucher fisik titip toko / warung / mitra reseller', 'color' => 'purple'],
                    ['code' => 'KOM', 'name' => 'Komisi & Fee Mitra', 'description' => 'Komisi mitra, cashback vendor/supplier, bagi hasil kemitraan', 'color' => 'amber'],
                    ['code' => 'REF', 'name' => 'Pengembalian / Refund / Kasbon', 'description' => 'Pengembalian dana vendor, setoran pelunasan kasbon karyawan/teknisi', 'color' => 'cyan'],
                    ['code' => 'INV', 'name' => 'Modal Masuk & Setoran Kas', 'description' => 'Suntikan modal owner, setoran kas awal, investasi', 'color' => 'teal'],
                    ['code' => 'OTH', 'name' => 'Pemasukan Lain-lain', 'description' => 'Pemasukan operasional dan non-operasional lainnya di luar tagihan', 'color' => 'gray'],
                ];

                foreach ($mitraCompanies as $mitraId) {
                    $hasCat = DB::table('income_categories')->where('company_id', $mitraId)->exists();
                    if (!$hasCat) {
                        foreach ($defaultIncomeCategories as $item) {
                            DB::table('income_categories')->insert(array_merge($item, [
                                'company_id' => $mitraId,
                                'is_active' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]));
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expense_categories') && Schema::hasColumn('expense_categories', 'company_id')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasTable('income_categories') && Schema::hasColumn('income_categories', 'company_id')) {
            Schema::table('income_categories', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};
