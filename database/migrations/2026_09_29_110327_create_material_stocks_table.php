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
        Schema::create('material_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('initial_stock', 10, 2)->default(0);
            $table->decimal('total_rolls', 10, 2)->default(0);
            $table->decimal('total_packs', 10, 2)->default(0);
            $table->decimal('total_pieces', 10, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['material_id', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_stocks');
    }
};
