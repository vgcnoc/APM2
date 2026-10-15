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
        Schema::create('pgs_material_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pgs_ticket_id')->constrained('pgs_tickets')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pgs_material_requests');
    }
};
