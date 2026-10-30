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
        if (!Schema::hasTable('network_maintenance_materials')) {
            Schema::create('network_maintenance_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('network_maintenance_id')->constrained('network_maintenances')->cascadeOnDelete();
                $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
                $table->unsignedBigInteger('material_transaction_item_id')->nullable();
                $table->string('trx_number', 100)->nullable();
                $table->decimal('quantity_prepared', 10, 2)->default(0);
                $table->decimal('quantity_used', 10, 2)->default(0);
                $table->decimal('quantity_returned', 10, 2)->default(0);
                $table->string('unit', 50)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('network_maintenance_id', 'nmm_maintenance_idx');
                $table->index('material_id', 'nmm_material_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_maintenance_materials');
    }
};

