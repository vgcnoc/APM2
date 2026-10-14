<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah field BPJS & Bank ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('bpjs_kesehatan')->default(false)->after('installation_fee');
            $table->string('bpjs_kesehatan_no')->nullable()->after('bpjs_kesehatan');
            $table->boolean('bpjs_ketenagakerjaan')->default(false)->after('bpjs_kesehatan_no');
            $table->string('bpjs_tk_no')->nullable()->after('bpjs_ketenagakerjaan');
            $table->string('bank_name')->nullable()->after('bpjs_tk_no');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_holder')->nullable()->after('bank_account_number');
        });

        // 2. Tambah rincian BPJS ke tabel payrolls
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('bpjs_kesehatan_employee', 15, 2)->default(0)->after('total_deduction');
            $table->decimal('bpjs_kesehatan_company', 15, 2)->default(0)->after('bpjs_kesehatan_employee');
            $table->decimal('bpjs_tk_jht_employee', 15, 2)->default(0)->after('bpjs_kesehatan_company');
            $table->decimal('bpjs_tk_jht_company', 15, 2)->default(0)->after('bpjs_tk_jht_employee');
            $table->decimal('bpjs_tk_jp_employee', 15, 2)->default(0)->after('bpjs_tk_jht_company');
            $table->decimal('bpjs_tk_jp_company', 15, 2)->default(0)->after('bpjs_tk_jp_employee');
            $table->decimal('bpjs_tk_jkk_company', 15, 2)->default(0)->after('bpjs_tk_jp_company');
            $table->decimal('bpjs_tk_jkm_company', 15, 2)->default(0)->after('bpjs_tk_jkk_company');
            $table->decimal('total_bpjs_employee', 15, 2)->default(0)->after('bpjs_tk_jkm_company');
            $table->decimal('total_bpjs_company', 15, 2)->default(0)->after('total_bpjs_employee');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bpjs_kesehatan',
                'bpjs_kesehatan_no',
                'bpjs_ketenagakerjaan',
                'bpjs_tk_no',
                'bank_name',
                'bank_account_number',
                'bank_account_holder',
            ]);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'bpjs_kesehatan_employee',
                'bpjs_kesehatan_company',
                'bpjs_tk_jht_employee',
                'bpjs_tk_jht_company',
                'bpjs_tk_jp_employee',
                'bpjs_tk_jp_company',
                'bpjs_tk_jkk_company',
                'bpjs_tk_jkm_company',
                'total_bpjs_employee',
                'total_bpjs_company',
            ]);
        });
    }
};
