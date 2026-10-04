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
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_reseller')->default(false)->after('package_id');
            $table->string('identity_photo')->nullable()->after('address');
            $table->decimal('latitude', 10, 8)->nullable()->after('identity_photo');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['is_reseller', 'identity_photo', 'latitude', 'longitude']);
        });
    }
};
