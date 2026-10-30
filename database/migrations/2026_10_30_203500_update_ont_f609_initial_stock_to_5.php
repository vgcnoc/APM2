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
        $m = Material::find(268);
        if ($m) {
            $m->stock = 5;
            $m->initial_stock = 5;
            $m->recalcUnitTotals();
            $m->saveQuietly();
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
