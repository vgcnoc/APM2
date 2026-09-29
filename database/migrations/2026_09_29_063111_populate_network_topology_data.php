<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Populate OLT PONs
        $olts = DB::table('olts')->get();
        foreach ($olts as $olt) {
            $ponCount = $olt->total_pon_ports > 0 ? $olt->total_pon_ports : 1;
            for ($i = 1; $i <= $ponCount; $i++) {
                DB::table('olt_pons')->updateOrInsert(
                    ['olt_id' => $olt->id, 'port_number' => $i],
                    [
                        'name' => "PON $i",
                        'capacity' => 64,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 2. Link ODCs to the first PON of their OLT if not linked
        $odcs = DB::table('odcs')->whereNull('pon_id')->get();
        foreach ($odcs as $odc) {
            $firstPon = DB::table('olt_pons')
                ->where('olt_id', $odc->olt_id)
                ->orderBy('port_number')
                ->first();
                
            if ($firstPon) {
                DB::table('odcs')->where('id', $odc->id)->update([
                    'pon_id' => $firstPon->id,
                    'splitter_ratio' => '1:8' // Default fallback
                ]);
            }
        }

        // 3. Populate ODP Ports
        $odps = DB::table('odps')->get();
        foreach ($odps as $odp) {
            $portCount = $odp->total_ports > 0 ? $odp->total_ports : 8;
            for ($i = 1; $i <= $portCount; $i++) {
                DB::table('odp_ports')->updateOrInsert(
                    ['odp_id' => $odp->id, 'port_number' => $i],
                    [
                        'status' => 'available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 4. Update ONTs with odp_port_id and set odp_ports status
        $onts = DB::table('onts')->whereNotNull('odp_id')->get();
        foreach ($onts as $ont) {
            if ($ont->port_number) {
                $odpPort = DB::table('odp_ports')
                    ->where('odp_id', $ont->odp_id)
                    ->where('port_number', $ont->port_number)
                    ->first();

                if ($odpPort) {
                    DB::table('onts')->where('id', $ont->id)->update([
                        'odp_port_id' => $odpPort->id
                    ]);

                    // Update port status
                    DB::table('odp_ports')->where('id', $odpPort->id)->update([
                        'status' => 'used'
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Data removal is handled by table dropping in the previous migration's down method
    }
};
