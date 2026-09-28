<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Olt;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Ont;
use App\Models\Customer;
use App\Models\MaterialTransaction;

class CheckNetworkAreaMismatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:check-area-mismatch {--fix : Attempt to fix by cascading parent area down to children}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and optionally fix area mismatches in the network hierarchy.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fix = $this->option('fix');
        $this->info("Checking for network area mismatches...");

        $mismatches = [
            'odc' => 0,
            'odp' => 0,
            'ont' => 0,
            'customer_ont' => 0,
            'customer_odp' => 0,
        ];

        // Check ODCs
        $odcs = Odc::with('olt')->get();
        foreach ($odcs as $odc) {
            if ($odc->olt && $odc->area_id !== $odc->olt->area_id) {
                $mismatches['odc']++;
                $this->warn("ODC ID: {$odc->id} ({$odc->name}) area_id ({$odc->area_id}) mismatches OLT area_id ({$odc->olt->area_id})");
                if ($fix) {
                    $odc->update(['area_id' => $odc->olt->area_id]);
                    $this->info("Fixed ODC ID: {$odc->id}");
                }
            }
        }

        // Check ODPs
        $odps = Odp::with('odc')->get();
        foreach ($odps as $odp) {
            if ($odp->odc && $odp->area_id !== $odp->odc->area_id) {
                $mismatches['odp']++;
                $this->warn("ODP ID: {$odp->id} ({$odp->name}) area_id ({$odp->area_id}) mismatches ODC area_id ({$odp->odc->area_id})");
                if ($fix) {
                    $odp->update(['area_id' => $odp->odc->area_id]);
                    $this->info("Fixed ODP ID: {$odp->id}");
                }
            }
        }

        // Check ONTs (against ODP if linked)
        $onts = Ont::with('odp.area', 'customer.area')->get();
        foreach ($onts as $ont) {
            if ($ont->odp && $ont->area_id != $ont->odp->area_id) {
                $mismatches['ont']++;
                $this->warn("ONT ID: {$ont->id} (SN: {$ont->serial_number}) area_id ({$ont->area_id}) mismatches ODP area_id ({$ont->odp->area_id})");
                if ($fix) {
                    $ont->update(['area_id' => $ont->odp->area_id]);
                    $this->info("Fixed ONT ID: {$ont->id} (matched with ODP)");
                }
            }
            
            // User requested check: Customer vs ODP mismatch
            if ($ont->customer && $ont->odp && $ont->customer->area_id != $ont->odp->area_id) {
                $mismatches['customer_odp']++;
                $customerArea = $ont->customer->area ? $ont->customer->area->name : $ont->customer->area_id;
                $odpArea = $ont->odp->area ? $ont->odp->area->name : $ont->odp->area_id;
                
                $this->error("\n⚠ AREA MISMATCH");
                $this->line("Customer:\n{$ont->customer->name} ({$ont->customer->customer_code})\nArea: {$customerArea}");
                $this->line("ODP:\n{$ont->odp->name}\nArea: {$odpArea}");
                $this->error("Status:\nINVALID\n");
                
                if ($fix) {
                    $this->warn("Skipping automatic fix for Customer-ODP mismatch. Manual review required.");
                }
            } elseif ($ont->customer && $ont->area_id != $ont->customer->area_id) {
                $mismatches['customer_ont']++;
                $this->warn("ONT ID: {$ont->id} (SN: {$ont->serial_number}) area_id ({$ont->area_id}) mismatches Customer area_id ({$ont->customer->area_id})");
                if ($fix) {
                    $ont->customer->update(['area_id' => $ont->area_id]);
                    $this->info("Fixed Customer ID: {$ont->customer->id} (matched with ONT)");
                }
            }
        }

        $this->table(
            ['Entity', 'Mismatches'],
            [
                ['ODC (vs OLT)', $mismatches['odc']],
                ['ODP (vs ODC)', $mismatches['odp']],
                ['ONT (vs ODP)', $mismatches['ont']],
                ['Customer (vs ONT)', $mismatches['customer_ont']],
            ]
        );

        if ($fix) {
            $this->info("Fixes applied successfully.");
        } else {
            $this->info("Run with --fix option to automatically cascade parent areas to children.");
        }
    }
}
