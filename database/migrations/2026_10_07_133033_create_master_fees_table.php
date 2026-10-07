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
        Schema::create('master_fees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'Fee Booking', 'Fee Survey', 'Fee Pasang', 'Fee Freelance per Paket', 'Bonus Target Booking'
            $table->decimal('nominal', 15, 2)->default(0);
            $table->string('mode')->default('Auto'); // 'Auto', 'Manual'
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_fees');
    }
};
