<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onts', function (Blueprint $table) {
            if (!Schema::hasColumn('onts', 'initial_rx_power')) {
                $table->decimal('initial_rx_power', 6, 2)->nullable()->after('rx_power')->comment('Redaman awal saat instalasi PSB dalam dBm');
            }
            if (!Schema::hasColumn('onts', 'last_rx_updated_at')) {
                $table->timestamp('last_rx_updated_at')->nullable()->after('initial_rx_power')->comment('Waktu pembaruan redaman terakhir dari tiket gangguan/pemeliharaan');
            }
            if (!Schema::hasColumn('onts', 'last_rx_ticket_ref')) {
                $table->string('last_rx_ticket_ref', 100)->nullable()->after('last_rx_updated_at')->comment('Nomor tiket PGS/PGK pembaruan redaman');
            }
            if (!Schema::hasColumn('onts', 'last_rx_notes')) {
                $table->text('last_rx_notes')->nullable()->after('last_rx_ticket_ref')->comment('Catatan/tindakan pembaruan redaman');
            }
        });

        // Backfill data awal instalasi untuk semua ONT yang sudah memiliki rx_power
        try {
            DB::table('onts')
                ->whereNull('initial_rx_power')
                ->whereNotNull('rx_power')
                ->update(['initial_rx_power' => DB::raw('rx_power')]);
        } catch (\Throwable $e) {
            // ignore if not supported in local test
        }
    }

    public function down(): void
    {
        Schema::table('onts', function (Blueprint $table) {
            $cols = [];
            foreach (['initial_rx_power', 'last_rx_updated_at', 'last_rx_ticket_ref', 'last_rx_notes'] as $col) {
                if (Schema::hasColumn('onts', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
