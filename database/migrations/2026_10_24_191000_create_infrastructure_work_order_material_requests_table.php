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
        Schema::dropIfExists('infrastructure_work_order_material_requests');

        Schema::create('infrastructure_work_order_material_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id');
            $table->string('item_name');
            $table->unsignedBigInteger('material_id')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('pcs');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('work_order_id', 'fk_infra_womr_wo')->references('id')->on('infrastructure_work_orders')->cascadeOnDelete();
            $table->foreign('material_id', 'fk_infra_womr_mat')->references('id')->on('materials')->nullOnDelete();
            $table->foreign('requested_by', 'fk_infra_womr_req')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by', 'fk_infra_womr_rev')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infrastructure_work_order_material_requests');
    }
};
