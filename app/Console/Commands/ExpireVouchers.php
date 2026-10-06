<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Voucher;
use App\Models\Radius\RadAcct;
use App\Services\RadiusService;
use Carbon\Carbon;

class ExpireVouchers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vouchers:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update expired vouchers based on usage time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting voucher expiration check...");

        // Get all vouchers that are not already expired
        $vouchers = Voucher::with('profile')->where('status', '!=', 'expired')->get();

        $expiredCount = 0;

        foreach ($vouchers as $voucher) {
            if (!$voucher->profile || !$voucher->profile->duration) {
                continue;
            }

            $durationLimit = RadiusService::parseDuration($voucher->profile->duration);
            
            if (!$durationLimit) {
                continue; // Cannot parse duration, skip
            }

            // Get total time from radacct
            $accts = RadAcct::where('username', $voucher->username)->get();
            
            $totalTime = 0;
            $firstLogin = null;
            
            foreach ($accts as $acct) {
                if (!$firstLogin || $acct->acctstarttime < $firstLogin) {
                    $firstLogin = $acct->acctstarttime;
                }

                if ($acct->acctstoptime) {
                    $totalTime += $acct->acctsessiontime;
                } else {
                    // Active session
                    $totalTime += $acct->liveSessionTime();
                }
            }

            $isExpired = false;

            if ($totalTime >= $durationLimit) {
                $isExpired = true;
            } elseif ($firstLogin) {
                $expiresAt = Carbon::parse($firstLogin)->addSeconds($durationLimit);
                if (now()->greaterThanOrEqualTo($expiresAt)) {
                    $isExpired = true;
                }
            }

            if ($isExpired) {
                // Voucher has expired
                $voucher->status = 'expired';
                $voucher->save();
                
                $this->info("Voucher {$voucher->username} marked as expired (Used: {$totalTime}s / Limit: {$durationLimit}s)");
                $expiredCount++;
            }
        }

        $this->info("Finished! Marked {$expiredCount} vouchers as expired.");
    }
}
