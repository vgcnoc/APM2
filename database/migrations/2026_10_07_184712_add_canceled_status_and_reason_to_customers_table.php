<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter enum to add 'canceled'
        DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('booking','survey','installing','active','suspended','terminated','canceled') NOT NULL DEFAULT 'booking'");
        
        Schema::table('customers', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
        // We do not strictly remove the enum value in down() because it could break existing rows, 
        // but normally we would convert 'canceled' back to 'booking' and alter enum back.
        // For simplicity and safety, we leave the enum alone in down().
    }
};
