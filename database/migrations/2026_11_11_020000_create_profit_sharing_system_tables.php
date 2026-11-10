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
        // 1. Skema Kerjasama / Profit Sharing Schemes
        if (!Schema::hasTable('profit_sharing_schemes')) {
            Schema::create('profit_sharing_schemes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('model')->default('gross_percentage'); // gross_percentage, net_percentage, flat_per_customer, tiered_volume, per_package
                $table->decimal('partner_percentage', 5, 2)->default(30.00);
                $table->decimal('isp_percentage', 5, 2)->default(70.00);
                $table->decimal('flat_per_customer_amount', 12, 2)->default(0.00);
                $table->decimal('registration_bonus', 12, 2)->default(0.00); // Bonus pasang baru per pelanggan
                $table->decimal('operational_deduction', 12, 2)->default(0.00); // Biaya pemeliharaan flat
                $table->boolean('deduct_tax')->default(false); // Potong PPN & BHP USO sebelum bagi hasil
                $table->json('tiered_rules')->nullable(); // [[min:1, max:50, percent:20], [min:51, max:100, percent:25], ...]
                $table->json('package_rules')->nullable(); // [package_id => [type: 'percent'|'flat', value: 25]]
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Kemitraan Aktif / Profit Sharing Partnerships
        if (!Schema::hasTable('profit_sharing_partnerships')) {
            Schema::create('profit_sharing_partnerships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('scheme_id')->constrained('profit_sharing_schemes')->onDelete('cascade');
                $table->string('partner_type')->default('area'); // 'area', 'user'
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('partner_name');
                $table->string('partner_phone')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->string('bank_account_name')->nullable();
                $table->date('contract_start_date')->nullable();
                $table->date('contract_end_date')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Rekapitulasi & Payout / Profit Sharing Settlements
        if (!Schema::hasTable('profit_sharing_settlements')) {
            Schema::create('profit_sharing_settlements', function (Blueprint $table) {
                $table->id();
                $table->string('settlement_number')->unique();
                $table->foreignId('partnership_id')->constrained('profit_sharing_partnerships')->onDelete('cascade');
                $table->unsignedSmallInteger('period_month');
                $table->unsignedSmallInteger('period_year');
                $table->unsignedInteger('total_customers')->default(0);
                $table->unsignedInteger('total_paid_invoices')->default(0);
                $table->decimal('total_gross_revenue', 14, 2)->default(0.00);
                $table->decimal('total_tax_deductions', 14, 2)->default(0.00);
                $table->decimal('total_operational_deductions', 14, 2)->default(0.00);
                $table->decimal('net_revenue', 14, 2)->default(0.00);
                $table->json('scheme_snapshot')->nullable();
                $table->decimal('partner_share_amount', 14, 2)->default(0.00);
                $table->decimal('isp_share_amount', 14, 2)->default(0.00);
                $table->string('status')->default('draft'); // 'draft', 'approved', 'paid'
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('payment_reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_sharing_settlements');
        Schema::dropIfExists('profit_sharing_partnerships');
        Schema::dropIfExists('profit_sharing_schemes');
    }
};

