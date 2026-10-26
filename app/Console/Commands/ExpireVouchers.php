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

        $expiredCount = \App\Http\Controllers\VoucherController::checkAndExpireVouchers();

        // Enforce shared devices limit (Hotspot mode / PPPoE / Vouchers)
        $excessKicked = app(\App\Services\RadiusService::class)->auditSimultaneousSessions();

        $this->info("Finished! Marked {$expiredCount} vouchers as expired, terminated {$excessKicked} excess concurrent sessions.");
    }
}
