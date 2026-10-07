<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('type')->default('auto'); // 'auto', 'manual'
            $table->string('description');
            $table->string('reference_id')->nullable(); // misal: 'payment_123'
            $table->date('incentive_date')->useCurrent();
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending'); // 'pending', 'paid'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentives');
    }
};
