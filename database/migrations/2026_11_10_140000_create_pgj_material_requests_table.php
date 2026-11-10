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
        if (!Schema::hasTable('pgj_material_requests')) {
            Schema::create('pgj_material_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pgj_ticket_id')->constrained('pgj_tickets')->cascadeOnDelete();
                $table->string('item_name')->nullable();
                $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->decimal('quantity', 10, 2);
                $table->string('unit', 50)->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
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
        Schema::dropIfExists('pgj_material_requests');
    }
};

