<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Models\Ont;

class SyncPorts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ports:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync OdpPort statuses and ODP used_ports with actual ONTs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing OdpPort table...');
        
        // Reset all to available
        OdpPort::query()->update(['status' => 'available']);

        // Set used for those with ONTs
        $onts = Ont::whereNotNull('odp_id')->whereNotNull('port_number')->get();
        foreach ($onts as $ont) {
            OdpPort::where('odp_id', $ont->odp_id)
                ->where('port_number', $ont->port_number)
                ->update(['status' => 'used']);
        }

        $this->info('Syncing ODP used_ports counts...');
        $odps = Odp::all();
        foreach ($odps as $odp) {
            $count = Ont::where('odp_id', $odp->id)->count();
            $odp->used_ports = $count;
            if ($count >= $odp->total_ports) {
                $odp->status = 'full';
            } else {
                if ($odp->status === 'full') $odp->status = 'active';
            }
            $odp->save();
        }

        $this->info('Done!');
    }
}
