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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable(); // e.g., 'Kabel', 'Konektor', 'Aksesoris'
            $table->string('unit')->default('pcs'); // e.g., 'meter', 'cm', 'pcs', 'rol'
            $table->decimal('stock', 10, 2)->default(0); // Decimal to allow for partial meters (e.g. 1.5 meter)
            $table->decimal('price_per_unit', 12, 2)->default(0)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
