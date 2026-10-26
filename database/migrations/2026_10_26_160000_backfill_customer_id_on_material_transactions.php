<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE material_transactions
            SET customer_id = source_id
            WHERE source_type = 'instalasi'
              AND customer_id IS NULL
              AND source_id IS NOT NULL
              AND source_id IN (SELECT id FROM customers)
        ");
    }

    public function down(): void
    {
        // No-op
    }
};
