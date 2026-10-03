<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ont;

class SyncOntIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-ont-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sequential ont_ids for ONTs that currently have a null ont_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill for missing ont_ids...');

        $onts = Ont::whereNull('ont_id')->get();
        if ($onts->isEmpty()) {
            $this->info('No ONTs found with a missing ont_id.');
            return;
        }

        $count = 0;
        foreach ($onts as $ont) {
            $lastOnt = Ont::whereNotNull('ont_id')
                            ->where('ont_id', 'like', 'V%')
                            ->orderByRaw('CAST(SUBSTRING(ont_id, 2) AS UNSIGNED) DESC')
                            ->first();

            if ($lastOnt) {
                $lastNumber = (int) substr($lastOnt->ont_id, 1);
                $ont->ont_id = 'V' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $ont->ont_id = 'V1001';
            }
            
            // disable timestamps to not update updated_at if not necessary
            $ont->timestamps = false;
            $ont->save();
            $count++;
            
            $this->line("Assigned {$ont->ont_id} to ONT ID {$ont->id}");
        }

        $this->info("Successfully generated ont_id for {$count} ONTs.");
    }
}
