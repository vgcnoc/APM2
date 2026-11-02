<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Material;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $material = Material::where('name', 'Frecon 150m')->first();
        if ($material) {
            $material->stock = 150.00;
            $material->total_rolls = 1.00;
            $material->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revert needed
    }
};
