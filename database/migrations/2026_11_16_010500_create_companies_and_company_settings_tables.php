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
        // 1. Buat Tabel Master Companies
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50)->unique();
                $table->string('brand_name')->nullable();
                $table->string('tagline')->nullable();
                $table->string('logo_url')->nullable();
                $table->string('favicon_url')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 100)->nullable();
                $table->text('address')->nullable();
                $table->string('website')->nullable();
                $table->text('invoice_kop_header')->nullable();
                $table->text('invoice_footer_notes')->nullable();
                $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
                $table->unsignedInteger('max_customers')->nullable();
                $table->json('allowed_modules')->nullable();
                $table->boolean('is_holding')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Buat Tabel Company Settings (Pengaturan Mandiri per Perusahaan)
        if (!Schema::hasTable('company_settings')) {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->string('key')->index();
                $table->longText('value')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'key'], 'company_settings_company_key_unique');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            });
        }

        // 3. Daftarkan Perusahaan Pusat (Holding) ID = 1 secara Otomatis
        $existingHolding = DB::table('companies')->where('id', 1)->first();
        if (!$existingHolding) {
            $companyName = DB::table('settings')->where('key', 'company_name')->value('value') ?: 'VIRUZS';
            $appName = DB::table('settings')->where('key', 'app_name')->value('value') ?: 'VIZBILL';
            $phone = DB::table('settings')->where('key', 'company_phone')->value('value') ?: '088976397034';
            $email = DB::table('settings')->where('key', 'company_email')->value('value') ?: 'helpdesk@viruzs.net';
            $address = DB::table('settings')->where('key', 'company_address')->value('value');

            DB::table('companies')->insert([
                'id' => 1,
                'name' => $companyName,
                'code' => 'PST',
                'brand_name' => $appName,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'status' => 'active',
                'is_holding' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Salin Setting Utama ke Company ID 1
        $settingsToCopy = [
            'payment_banks',
            'invoice_issue_date',
            'billing_cycle',
            'tax_ppn',
            'isolate_day',
            'isolate_days',
            'isolate_time',
            'midtrans_server_key',
            'midtrans_client_key',
            'midtrans_merchant_id',
            'tripay_api_key',
            'tripay_private_key',
            'tripay_merchant_code',
        ];

        foreach ($settingsToCopy as $k) {
            $val = DB::table('settings')->where('key', $k)->value('value');
            if ($val !== null) {
                DB::table('company_settings')->updateOrInsert(
                    ['company_id' => 1, 'key' => $k],
                    ['value' => $val, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        // 5. Tambahkan Kolom company_id (DEFAULT 1) ke Seluruh Tabel Operasional
        $targetTables = [
            'users',
            'customers',
            'invoices',
            'payments',
            'areas',
            'internet_packages',
            'tickets',
            'pgk_tickets',
            'pgj_tickets',
            'pgs_tickets',
            'cbp_requests',
            'employees',
            'attendances',
            'attendance_leaves',
            'payrolls',
            'materials',
            'material_transactions',
            'vouchers',
            'resellers',
            'project_invoices',
            'expenses',
            'incomes',
        ];

        foreach ($targetTables as $tbl) {
            if (Schema::hasTable($tbl) && !Schema::hasColumn($tbl, 'company_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->default(1)->after('id')->index();
                });

                // Pastikan data lama yang null/0 otomatis terupdate ke 1
                DB::table($tbl)->whereNull('company_id')->orWhere('company_id', 0)->update(['company_id' => 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $targetTables = [
            'users',
            'customers',
            'invoices',
            'payments',
            'areas',
            'internet_packages',
            'tickets',
            'pgk_tickets',
            'pgj_tickets',
            'pgs_tickets',
            'cbp_requests',
            'employees',
            'attendances',
            'attendance_leaves',
            'payrolls',
            'materials',
            'material_transactions',
            'vouchers',
            'resellers',
            'project_invoices',
            'expenses',
            'incomes',
        ];

        foreach ($targetTables as $tbl) {
            if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'company_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropColumn('company_id');
                });
            }
        }

        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('companies');
    }
};
