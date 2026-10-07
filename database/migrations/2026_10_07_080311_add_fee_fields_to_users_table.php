<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('booking_fee', 15, 2)->default(0)->after('incentive_rate');
            $table->decimal('installation_fee', 15, 2)->default(0)->after('booking_fee');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['booking_fee', 'installation_fee']);
        });
    }
};
