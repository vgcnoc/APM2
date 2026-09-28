<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Ont;
use App\Models\TechnicianSchedule;

class FixCorruptedOnts extends Command
{
    protected $signature = 'app:fix-corrupted-onts';
    protected $description = 'Fix corrupted ONTs created by the old assignOnt method';

    public function handle()
    {
        $this->info('Starting to fix corrupted ONTs...');
        
        // Find customers in 'installing' status with an ONT that has a generated serial number
        $customers = Customer::where('status', 'installing')->get();
        $fixed = 0;
        
        foreach ($customers as $customer) {
            $ont = Ont::where('customer_id', $customer->id)->where('serial_number', 'like', 'SN-%')->first();
            
            if ($ont) {
                // Find the real ONT SN from technician schedule notes
                $schedule = TechnicianSchedule::where('customer_id', $customer->id)
                    ->where('type', 'installation')
                    ->first();
                
                if ($schedule && $schedule->notes) {
                    // Extract SN from notes: (SN: AUTO-GZFTRU-1790513459-1)
                    if (preg_match('/\(SN:\s*([^\)]+)\)/', $schedule->notes, $matches)) {
                        $realSn = trim($matches[1]);
                        
                        // Find the real ONT in inventory
                        $realOnt = Ont::where('serial_number', $realSn)->first();
                        
                        if ($realOnt) {
                            $this->info("Fixing customer {$customer->id} (Code: {$customer->customer_code}). Replacing dummy ONT {$ont->serial_number} with real ONT {$realOnt->serial_number}");
                            
                            // Copy data from dummy ONT to real ONT
                            $realOnt->update([
                                'customer_id' => $customer->id,
                                'odp_id' => $ont->odp_id,
                                'port_number' => $ont->port_number,
                                'rx_power' => $ont->rx_power,
                                'start_time' => $ont->start_time,
                                'end_time' => $ont->end_time,
                                'photo_odp' => $ont->photo_odp,
                                'photo_installation' => $ont->photo_installation,
                                'photo_ont' => $ont->photo_ont,
                                'photo_customer' => $ont->photo_customer,
                                'photo_redaman' => $ont->photo_redaman,
                                'status' => 'active',
                            ]);
                            
                            // Delete dummy ONT
                            $ont->delete();
                            $fixed++;
                        } else {
                            $this->warn("Real ONT not found for SN {$realSn} (Customer {$customer->id})");
                        }
                    }
                }
            }
        }
        
        $this->info("Fixed {$fixed} corrupted ONTs!");
    }
}
