<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('odp_id')->nullable()->constrained('odps')->nullOnDelete();
            $table->foreignId('surveyor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('signal_loss', 6, 2)->nullable()->comment('Redaman dalam dBm');
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->boolean('port_available')->default(false);
            $table->enum('feasibility', ['feasible', 'not_feasible', 'conditional'])->default('feasible');
            $table->date('survey_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->timestamps();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
