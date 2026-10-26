<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Radius\RadAcct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BillingIsolationService
{
    public function __construct(private BillingIsolationSchedule $schedule, private RadiusService $radius) {}

    public function hasDueBalance(Customer $customer): bool
    {
        if (in_array($customer->service_status, ['gratis', 'free_home'], true)) return false;
        $settings = $this->schedule->settings();
        $now = now();
        $invoices = Invoice::where('customer_id', $customer->id)->where('is_reseller_balance', false)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])->whereNotNull('issued_date')
            ->withSum(['payments as verified_paid' => fn ($q) => $q->where('status', 'verified')], 'amount')->get();
        foreach ($invoices as $invoice) {
            if ((float) $invoice->amount <= (float) ($invoice->verified_paid ?? 0)) continue;
            if ($invoice->promise_date && $now->lte(\Carbon\CarbonImmutable::parse($invoice->promise_date->format('Y-m-d'), $this->schedule->timezone())->endOfDay())) continue;
            if ($this->schedule->deadline($invoice, $settings)->lte($now)) return true;
        }
        return false;
    }

    public function reconcile(Customer $customer): bool
    {
        return Cache::lock('customer:connection-access:'.$customer->id, 180)->get(function () use ($customer) {
            $sync = DB::transaction(function () use ($customer) {
                $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
                if (!in_array($customer->status, ['active', 'suspended'], true)) return null;
                $due = $this->hasDueBalance($customer);
                if ($customer->status === 'active' && $due) {
                    $customer->forceFill(['status' => 'suspended', 'billing_isolated_at' => now(), 'billing_isolation_pending' => true])->saveQuietly();
                    Log::info('[BILLING] Scheduled isolation reached', ['customer_id' => $customer->id]);
                } elseif ($customer->status === 'suspended' && $customer->billing_isolated_at && !$due && !$customer->suspend_start && !$customer->suspend_end) {
                    $customer->forceFill(['status' => 'active', 'billing_isolated_at' => null, 'billing_isolation_pending' => true])->saveQuietly();
                }
                return $customer->billing_isolation_pending ? $customer : null;
            });
            if (!$sync) return false;
            $this->syncConnection($sync);
            return true;
        }) ?: false;
    }

    public function releaseAfterPayment(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if ($customer->status !== 'suspended') return;
            // Old automatic suspensions had no reason. Dated manual suspensions stay manual.
            $automatic = !$customer->suspend_start && !$customer->suspend_end;
            if (!$automatic || $this->hasDueBalance($customer)) return;
            $customer->forceFill(['status' => 'active', 'suspend_start' => null, 'suspend_end' => null,
                'billing_isolated_at' => null, 'billing_isolation_pending' => true])->saveQuietly();
            // Never reopen access for a payment transaction that may still roll back.
            DB::afterCommit(fn () => $this->reconcile($customer));
        });
    }

    private function syncConnection(Customer $customer): void
    {
        try {
            $customer->refresh();
            $customer->load('onts', 'ont', 'package');
            $usernames = $customer->onts->flatMap(fn ($ont) => array_filter([$ont->pppoe_user, $ont->hotspot_user]))->unique();
            if ($usernames->isNotEmpty() && !$this->radius->enabled()) throw new RuntimeException('RADIUS integration is disabled');
            if ($this->radius->enabled()) {
                $this->radius->syncIsolirGroup();
                foreach ($customer->onts as $ont) {
                    $copy = clone $customer;
                    $copy->setRelation('ont', $ont);
                    $this->radius->syncCustomer($copy);
                }
                foreach ($usernames as $username) {
                    $disconnected = $this->radius->disconnect($username);
                    if (!$disconnected && RadAcct::online()->fresh()->where('username', $username)->exists()) throw new RuntimeException('Active session disconnect not acknowledged');
                }
            }
            $customer->forceFill(['billing_isolation_pending' => false])->saveQuietly();
        } catch (Throwable $e) {
            Log::warning('[BILLING] Connection sync pending; scheduler will retry', ['customer_id' => $customer->id]);
        }
    }
}
