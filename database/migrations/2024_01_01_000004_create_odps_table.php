<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odc_id')->constrained('odcs')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('total_ports')->default(8);
            $table->integer('used_ports')->default(0);
            $table->enum('status', ['active', 'inactive', 'full', 'maintenance'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('odc_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odps');
    }
};
