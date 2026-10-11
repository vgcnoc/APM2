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
        \Illuminate\Support\Facades\DB::table('materials')
            ->where(function ($q) {
                $q->whereNull('initial_stock')
                  ->orWhere('initial_stock', 0);
            })
            ->where('stock', '>', 0)
            ->update(['initial_stock' => \Illuminate\Support\Facades\DB::raw('stock')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
