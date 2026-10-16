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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('status', 30)->default('active')->after('employee_type');
            $table->date('resign_date')->nullable()->after('status');
            $table->string('resign_reason', 255)->nullable()->after('resign_date');
            $table->text('resign_notes')->nullable()->after('resign_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['status', 'resign_date', 'resign_reason', 'resign_notes']);
        });
    }
};
