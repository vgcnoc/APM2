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
        if (!Schema::hasTable('infrastructure_work_order_material_requests')) {
            Schema::create('infrastructure_work_order_material_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_order_id')->constrained('infrastructure_work_orders')->cascadeOnDelete();
                $table->string('item_name');
                $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->decimal('quantity', 10, 2);
                $table->string('unit')->default('pcs');
                $table->text('notes')->nullable();
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infrastructure_work_order_material_requests');
    }
};
