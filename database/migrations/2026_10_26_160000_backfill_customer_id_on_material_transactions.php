<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('material_transactions')
            ->where('source_type', 'instalasi')
            ->whereNull('customer_id')
            ->whereNotNull('source_id')
            ->update([
                'customer_id' => DB::raw('source_id')
            ]);
    }

    public function down(): void
    {
        // No-op
    }
};
