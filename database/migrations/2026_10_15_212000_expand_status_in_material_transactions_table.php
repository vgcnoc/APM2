<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('material_transactions')) {
            DB::statement("ALTER TABLE material_transactions MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'approved'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('material_transactions')) {
            DB::statement("ALTER TABLE material_transactions MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved'");
        }
    }
};
