<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\BillingIsolationService;
use Illuminate\Console\Command;

class SuspendOverdueCustomers extends Command
{
    protected $signature = 'billing:suspend-overdue {--dry-run : Count eligible customers without changing data or connections} {--limit=0 : Maximum connection updates; 0 processes all due customers}';
    protected $description = 'Apply invoice isolation dates and times, then retry pending connection changes.';

    public function handle(BillingIsolationService $isolation): int
    {
        if (\Illuminate\Support\Facades\Cache::get('billing:isolation:deploying')) {
            $this->info('Billing isolation rollout validation is in progress.');
            return self::SUCCESS;
        }
        $count = 0;
        $limit = max(0, (int) $this->option('limit'));
        Customer::where(function ($q) {
            $q->where(function ($active) {
                $active->where('status', 'active')->whereHas('invoices', function ($invoice) {
                    $invoice->whereIn('status', ['unpaid', 'partial', 'overdue'])->where('is_reseller_balance', false)
                        ->where(fn ($date) => $date->whereNull('isolation_at')->orWhere('isolation_at', '<=', now()));
                });
            })->orWhereNotNull('billing_isolated_at')->orWhere('billing_isolation_pending', true);
        })->chunkById(200, function ($customers) use ($isolation, &$count, $limit) {
            foreach ($customers as $customer) {
                if ($this->option('dry-run')) {
                    if ($customer->status === 'active' && $isolation->hasDueBalance($customer)) $count++;
                } elseif ($isolation->reconcile($customer)) {
                    $count++;
                    if ($limit && $count >= $limit) return false;
                }
            }
        });
        $this->info(($this->option('dry-run') ? 'Eligible for isolation: ' : 'Connection states processed: ').$count);
        return self::SUCCESS;
    }
}
