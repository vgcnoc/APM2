<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('connection_disabled')->default(false);
            $table->boolean('connection_access_pending')->default(false);
            $table->json('connection_disable_state')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn([
            'connection_disabled', 'connection_access_pending', 'connection_disable_state',
        ]));
    }
};
