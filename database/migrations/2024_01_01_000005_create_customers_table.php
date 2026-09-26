<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->text('address');
            $table->string('area')->nullable();
            $table->string('identity_photo')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->foreignId('package_id')->nullable()->constrained('internet_packages')->nullOnDelete();
            $table->enum('status', ['booking', 'survey', 'installing', 'active', 'suspended', 'terminated'])->default('booking');
            $table->date('registration_date')->nullable();
            $table->date('activation_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('customer_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
