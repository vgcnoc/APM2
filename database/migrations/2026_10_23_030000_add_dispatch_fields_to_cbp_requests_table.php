<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cbp_requests', function (Blueprint $table) {
            $table->timestamp('sent_to_schedule_at')->nullable()->after('created_by');
            $table->foreignId('sent_to_schedule_by')->nullable()->after('sent_to_schedule_at')->constrained('users')->nullOnDelete();
            $table->text('dispatch_notes')->nullable()->after('sent_to_schedule_by');
        });

        // Set existing assigned or completed records so they remain visible in Jadwal / Laporan
        DB::table('cbp_requests')
            ->whereIn('status', ['assigned', 'completed'])
            ->whereNull('sent_to_schedule_at')
            ->update([
                'sent_to_schedule_at' => DB::raw('created_at')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cbp_requests', function (Blueprint $table) {
            $table->dropForeign(['sent_to_schedule_by']);
            $table->dropColumn(['sent_to_schedule_at', 'sent_to_schedule_by', 'dispatch_notes']);
        });
    }
};
