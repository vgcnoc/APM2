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
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn(['name', 'phone', 'address', 'area_id', 'latitude', 'longitude', 'ktp_photo', 'installation_fee']);
            $table->foreignId('customer_id')->nullable()->after('id')->constrained('customers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('area_id')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('ktp_photo')->nullable();
            $table->decimal('installation_fee', 15, 2)->default(0);
        });
    }
};
