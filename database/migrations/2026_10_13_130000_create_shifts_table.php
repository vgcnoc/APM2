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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('start_time', 10); // Format H:i e.g. 08:00
            $table->string('end_time', 10);   // Format H:i e.g. 17:00
            $table->boolean('is_night_shift')->default(false);
            $table->string('color', 30)->nullable()->default('blue');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tambahkan shift_id ke tabel employees jika tabel employees ada
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'shift_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('shift_id')->nullable()->after('position')->constrained('shifts')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'shift_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            });
        }

        Schema::dropIfExists('shifts');
    }
};
