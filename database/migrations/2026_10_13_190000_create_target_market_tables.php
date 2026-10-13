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
        Schema::create('target_market_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('concept'); // milestone_volume, area_expansion, package_tier, hybrid
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedInteger('target_quota')->default(10);
            $table->string('target_role')->nullable(); // null = semua karyawan
            $table->decimal('reward_flat', 15, 2)->default(0); // bonus per customer
            $table->decimal('reward_bonus_target', 15, 2)->default(0); // bonus jika kuota target tembus
            $table->json('rules')->nullable(); // tier brackets, target area ids, package bonuses
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_disburse')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('target_market_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('target_market_programs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('base_reward', 15, 2)->default(0);
            $table->decimal('tier_reward', 15, 2)->default(0);
            $table->decimal('area_reward', 15, 2)->default(0);
            $table->decimal('package_reward', 15, 2)->default(0);
            $table->decimal('target_achieved_reward', 15, 2)->default(0);
            $table->decimal('total_reward', 15, 2)->default(0);
            $table->json('concept_breakdown')->nullable();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->string('status')->default('approved'); // pending, approved, synced_payroll
            $table->foreignId('incentive_id')->nullable()->constrained('incentives')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_market_logs');
        Schema::dropIfExists('target_market_programs');
    }
};
