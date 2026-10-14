<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah referral_code pada customers jika belum ada
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'referral_code')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('referral_code')->nullable()->after('sales_id')->index();
            });
        }

        // 2. Tabel Profil Afiliasi / Sales Partner
        if (!Schema::hasTable('affiliate_profiles')) {
            Schema::create('affiliate_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('referral_code')->unique()->index();
                $table->foreignId('upline_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('whatsapp')->nullable();
                $table->text('address')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->string('bank_account_holder')->nullable();
                $table->decimal('balance', 15, 2)->default(0);
                $table->decimal('total_earned', 15, 2)->default(0);
                $table->decimal('total_withdrawn', 15, 2)->default(0);
                $table->string('status')->default('active'); // active, pending, suspended
                $table->timestamps();
            });
        }

        // 3. Tabel Log Komisi Afiliasi (Multi-Tier)
        if (!Schema::hasTable('affiliate_commissions')) {
            Schema::create('affiliate_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete(); // Downline pencetus jika komisi tier
                $table->string('type'); // 'booking', 'monthly_direct', 'upline_tier_1', 'upline_tier_2', 'manual'
                $table->unsignedTinyInteger('tier_level')->default(0); // 0 = Direct, 1 = Downline Level 1, 2 = Downline Level 2
                $table->decimal('amount', 15, 2);
                $table->string('description');
                $table->string('status')->default('approved'); // pending, approved, paid, cancelled
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Tabel Permintaan Penarikan Dana (Withdrawal)
        if (!Schema::hasTable('affiliate_withdrawals')) {
            Schema::create('affiliate_withdrawals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->decimal('fee', 15, 2)->default(0);
                $table->decimal('net_amount', 15, 2);
                $table->string('bank_name');
                $table->string('bank_account_number');
                $table->string('bank_account_holder');
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->string('reference_no')->nullable();
                $table->string('receipt_proof')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_withdrawals');
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliate_profiles');

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'referral_code')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('referral_code');
            });
        }
    }
};
