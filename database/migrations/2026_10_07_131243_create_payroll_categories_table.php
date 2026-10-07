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
        Schema::create('payroll_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('incentive'); // 'incentive', 'deduction'
            $table->string('mode')->default('manual'); // 'auto', 'manual'
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_categories');
    }
};
