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
        Schema::table('pgj_tickets', function (Blueprint $table) {
            $table->foreignId('olt_id')->nullable()->after('area_id')->constrained('olts')->nullOnDelete();
            $table->string('backbone_source')->nullable()->after('segment_type'); // Tipe sumber: Feeder OLT, Uplink Provider, Inter-POP, dll
            $table->string('provider_name')->nullable()->after('backbone_source'); // Nama upstream ISP / Provider jika sumber internet
            $table->string('circuit_id')->nullable()->after('provider_name'); // No. ID Sirkit / CID Provider
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pgj_tickets', function (Blueprint $table) {
            $table->dropForeign(['olt_id']);
            $table->dropColumn(['olt_id', 'backbone_source', 'provider_name', 'circuit_id']);
        });
    }
};
