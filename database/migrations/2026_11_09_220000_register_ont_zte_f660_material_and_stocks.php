<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\Area;
use App\Models\Ont;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Standarisasi Brand & Model pada seluruh data perangkat ONT F660
        Ont::where(function ($q) {
            $q->where('brand', 'like', '%660%')
              ->orWhere('model', 'like', '%660%');
        })->get()->each(function ($ont) {
            $ont->brand = 'ZTE';
            $ont->model = 'F660';
            $ont->saveQuietly();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep data intact
    }
};

