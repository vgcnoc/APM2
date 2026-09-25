<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odp_id')->constrained('odps')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->string('mac_address', 17)->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->integer('port_number');
            $table->decimal('rx_power', 6, 2)->nullable()->comment('Redaman terima dalam dBm');
            $table->decimal('tx_power', 6, 2)->nullable()->comment('Daya kirim dalam dBm');
            $table->enum('status', ['active', 'inactive', 'los', 'damaged'])->default('inactive');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('odp_id');
            $table->index('customer_id');
            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onts');
    }
};
