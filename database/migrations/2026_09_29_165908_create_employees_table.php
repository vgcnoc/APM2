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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('photo')->nullable();
            $table->string('name');
            $table->string('iak_number')->nullable()->unique();
            $table->string('position')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->string('branch')->nullable();
            $table->date('join_date')->nullable();
            $table->string('employee_type')->nullable(); // Tetap, Kontrak, dll
            $table->string('payment_method')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
