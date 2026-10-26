<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\BillingIsolationSchedule;
use App\Services\BillingIsolationService;
use App\Services\RadiusService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BillingIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'radius.connection' => 'sqlite', 'radius.enabled' => false, 'cache.default' => 'array', 'session.driver' => 'array',
            'app.timezone' => 'UTC', 'billing.timezone' => 'Asia/Jakarta']);
        DB::purge('sqlite');
        Carbon::setTestNow(Carbon::parse('2026-10-13 13:43:59', 'UTC'));
        Schema::create('settings', function (Blueprint $t) { $t->id(); $t->string('key')->unique(); $t->text('value')->nullable(); $t->timestamps(); });
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name')->default('Test'); $t->string('customer_code')->default('TEST');
            $t->string('status')->default('active'); $t->string('service_status')->default('berbayar');
            $t->integer('package_id')->nullable(); $t->boolean('connection_disabled')->default(false);
            $t->string('billing_cycle')->nullable(); $t->string('billing_type')->nullable(); $t->integer('billing_day')->nullable();
            $t->date('activation_date')->nullable(); $t->date('registration_date')->nullable(); $t->integer('tax_ppn')->nullable();
            $t->date('suspend_start')->nullable(); $t->date('suspend_end')->nullable(); $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id(); $t->integer('customer_id'); $t->string('invoice_number')->nullable();
            $t->integer('amount')->default(150000); $t->date('issued_date'); $t->date('due_date');
            $t->string('status')->default('unpaid'); $t->boolean('is_reseller_balance')->default(false); $t->date('promise_date')->nullable(); $t->timestamps();
            $t->integer('period_month')->nullable(); $t->integer('period_year')->nullable(); $t->string('billing_cycle')->nullable(); $t->string('billing_type')->nullable();
        });
        Schema::create('payments', function (Blueprint $t) { $t->id(); $t->integer('invoice_id'); $t->integer('customer_id'); $t->integer('amount'); $t->string('status'); $t->timestamps(); });
        Schema::create('onts', function (Blueprint $t) { $t->id(); $t->integer('customer_id'); $t->string('pppoe_user')->nullable(); $t->string('hotspot_user')->nullable(); });
        Schema::create('internet_packages', function (Blueprint $t) { $t->id(); $t->string('name'); $t->integer('price')->default(150000); });
        (require base_path('database/migrations/2026_10_26_210000_add_scheduled_billing_isolation.php'))->up();
        Setting::set('isolate_day', '13'); Setting::set('isolate_time', '20:44');
    }

    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    private function customer(array $attributes = []): Customer
    {
        return Customer::findOrFail(DB::table('customers')->insertGetId($attributes ?: ['status' => 'active']));
    }

    private function invoice(Customer $customer, array $attributes = []): Invoice
    {
        return Invoice::create(array_replace(['customer_id' => $customer->id, 'issued_date' => '2026-10-01', 'due_date' => '2026-10-13', 'amount' => 150000, 'status' => 'unpaid'], $attributes));
    }

    private function radius(bool $available = true): RadiusService
    {
        $radius = \Mockery::mock(RadiusService::class);
        $radius->shouldReceive('enabled')->andReturn(true);
        $radius->shouldReceive('syncIsolirGroup')->andReturnNull();
        if ($available) {
            $radius->shouldReceive('syncCustomer')->andReturnNull();
            $radius->shouldReceive('disconnect')->andReturn(1);
        } else {
            $radius->shouldReceive('syncCustomer')->andThrow(new \RuntimeException('Unavailable'));
        }
        $this->app->instance(RadiusService::class, $radius);
        $this->app->forgetInstance(BillingIsolationService::class);
        return $radius;
    }

    public function test_deadline_keeps_clock_time_in_wib_even_when_app_and_server_use_utc(): void
    {
        $invoice = $this->invoice($this->customer());
        $this->assertSame('2026-10-13 13:44:00', $invoice->fresh()->isolation_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-13 20:44:00', $invoice->fresh()->isolation_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertFalse(app(BillingIsolationService::class)->hasDueBalance($invoice->customer));
        Carbon::setTestNow(Carbon::parse('2026-10-13 13:44:00', 'UTC'));
        $this->assertTrue(app(BillingIsolationService::class)->hasDueBalance($invoice->customer));
    }

    public function test_monthly_isolation_is_the_first_date_on_or_after_the_invoice_issue_date(): void
    {
        $schedule = app(BillingIsolationSchedule::class);
        $this->assertSame('2026-11-13 20:44', $schedule->forIssueDate('2026-10-22')->setTimezone('Asia/Jakarta')->format('Y-m-d H:i'));
        $this->assertSame('2027-01-13', $schedule->forIssueDate('2026-12-31')->setTimezone('Asia/Jakarta')->toDateString());
        Setting::set('isolate_day', '31');
        $this->assertSame('2027-02-28', $schedule->forIssueDate('2027-02-01')->setTimezone('Asia/Jakarta')->toDateString());
        $this->assertSame('2028-02-29', $schedule->forIssueDate('2028-02-01')->setTimezone('Asia/Jakarta')->toDateString());
    }

    public function test_scheduler_does_not_isolate_early_and_catches_up_once_after_downtime(): void
    {
        $customer = $this->customer(); $this->invoice($customer); $this->radius();
        $this->artisan('billing:suspend-overdue')->assertExitCode(0);
        $this->assertSame('active', $customer->fresh()->status);
        Carbon::setTestNow(Carbon::parse('2026-10-14 05:00:00', 'UTC'));
        $this->artisan('billing:suspend-overdue')->assertExitCode(0);
        $this->assertSame('suspended', $customer->fresh()->status);
        $isolated = $customer->fresh()->billing_isolated_at->toIso8601String();
        $this->assertFalse($customer->fresh()->billing_isolation_pending);
        $this->artisan('billing:suspend-overdue')->assertExitCode(0);
        $this->assertSame($isolated, $customer->fresh()->billing_isolated_at->toIso8601String());
    }

    public function test_promise_date_is_honored_until_the_end_of_that_billing_day(): void
    {
        $customer = $this->customer(); $this->invoice($customer, ['promise_date' => '2026-10-13']);
        Carbon::setTestNow(Carbon::parse('2026-10-13 16:59:59', 'UTC'));
        $this->assertFalse(app(BillingIsolationService::class)->hasDueBalance($customer));
        Carbon::setTestNow(Carbon::parse('2026-10-13 17:00:00', 'UTC'));
        $this->assertTrue(app(BillingIsolationService::class)->hasDueBalance($customer));
    }

    public function test_only_real_unpaid_balances_are_eligible_not_paid_free_cancelled_or_topups(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 05:00:00', 'UTC'));
        foreach ([['status' => 'paid'], ['status' => 'cancelled'], ['is_reseller_balance' => true], ['amount' => 0]] as $attributes) {
            $customer = $this->customer(); $this->invoice($customer, $attributes);
            $this->assertFalse(app(BillingIsolationService::class)->hasDueBalance($customer));
        }
        foreach (['gratis', 'free_home'] as $status) {
            $customer = $this->customer(['service_status' => $status]); $this->invoice($customer);
            $this->assertFalse(app(BillingIsolationService::class)->hasDueBalance($customer));
        }
        $customer = $this->customer(); $invoice = $this->invoice($customer, ['status' => 'overdue']);
        DB::table('payments')->insert(['invoice_id' => $invoice->id, 'customer_id' => $customer->id, 'amount' => 150000, 'status' => 'verified']);
        $this->assertFalse(app(BillingIsolationService::class)->hasDueBalance($customer));
        DB::table('payments')->update(['status' => 'pending']);
        $this->assertTrue(app(BillingIsolationService::class)->hasDueBalance($customer));
    }

    public function test_router_failure_remains_pending_and_is_retried_after_customer_is_suspended(): void
    {
        $customer = $this->customer(); $this->invoice($customer);
        DB::table('onts')->insert(['customer_id' => $customer->id, 'pppoe_user' => 'alice']);
        Carbon::setTestNow(Carbon::parse('2026-10-14 05:00:00', 'UTC'));
        $this->radius(false);
        app(BillingIsolationService::class)->reconcile($customer);
        $this->assertTrue($customer->fresh()->billing_isolation_pending);
        $this->assertSame('suspended', $customer->fresh()->status);
        $this->radius();
        app(BillingIsolationService::class)->reconcile($customer);
        $this->assertFalse($customer->fresh()->billing_isolation_pending);
    }

    public function test_paying_one_invoice_does_not_reopen_access_when_another_due_invoice_remains(): void
    {
        $customer = $this->customer(); $one = $this->invoice($customer); $two = $this->invoice($customer);
        Carbon::setTestNow(Carbon::parse('2026-10-14 05:00:00', 'UTC')); $this->radius();
        app(BillingIsolationService::class)->reconcile($customer);
        Payment::create(['invoice_id' => $one->id, 'customer_id' => $customer->id, 'amount' => 150000, 'status' => 'verified']);
        $this->assertSame('suspended', $customer->fresh()->status);
        Payment::create(['invoice_id' => $two->id, 'customer_id' => $customer->id, 'amount' => 150000, 'status' => 'verified']);
        $this->assertSame('active', $customer->fresh()->status);
        $this->assertNull($customer->fresh()->billing_isolated_at);
    }

    public function test_payment_does_not_clear_manual_disable_or_dated_manual_suspension(): void
    {
        $customer = $this->customer(['status' => 'suspended', 'connection_disabled' => true, 'suspend_start' => '2026-10-01']);
        $invoice = $this->invoice($customer);
        Payment::create(['invoice_id' => $invoice->id, 'customer_id' => $customer->id, 'amount' => 150000, 'status' => 'verified']);
        $this->assertSame('suspended', $customer->fresh()->status);
        $this->assertTrue($customer->fresh()->connection_disabled);
    }

    public function test_changing_settings_recalculates_only_outstanding_invoice_deadlines(): void
    {
        $customer = $this->customer(); $open = $this->invoice($customer); $paid = $this->invoice($customer, ['status' => 'paid']);
        Setting::set('isolate_day', '15'); Setting::set('isolate_time', '09:30');
        $this->assertSame(1, app(BillingIsolationSchedule::class)->refreshOutstanding());
        $this->assertSame('2026-10-15 02:30:00', $open->fresh()->isolation_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-13 13:44:00', $paid->fresh()->isolation_at->format('Y-m-d H:i:s'));
    }

    public function test_pending_payment_does_not_reduce_the_verified_outstanding_balance(): void
    {
        $customer = $this->customer(); $invoice = $this->invoice($customer);
        Payment::create(['invoice_id' => $invoice->id, 'customer_id' => $customer->id, 'amount' => 150000, 'status' => 'pending']);
        $this->assertSame(150000, $invoice->fresh()->remaining);
        $this->assertSame('unpaid', $invoice->fresh()->status);
    }

    public function test_dry_run_does_not_modify_customer_or_connect_to_radius(): void
    {
        $customer = $this->customer(); $this->invoice($customer);
        Carbon::setTestNow(Carbon::parse('2026-10-14 05:00:00', 'UTC'));
        $this->mock(RadiusService::class)->shouldNotReceive('syncCustomer');
        $this->artisan('billing:suspend-overdue', ['--dry-run' => true])->expectsOutput('Eligible for isolation: 1')->assertExitCode(0);
        $this->assertSame('active', $customer->fresh()->status);
    }

    public function test_routine_invoices_use_the_billing_day_even_while_utc_is_still_the_previous_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 17:00:00', 'UTC'));
        Setting::set('billing_cycle', 'calendar'); Setting::set('invoice_issue_date', '1');
        DB::table('internet_packages')->insert(['id' => 1, 'name' => 'Test Package']);
        $customer = $this->customer(['billing_cycle' => 'calendar', 'package_id' => 1]);
        $this->artisan('app:generate-invoices')->assertExitCode(0);
        $invoice = Invoice::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('2026-10-01', $invoice->issued_date->toDateString());
        $this->assertSame('2026-10-13 13:44:00', $invoice->isolation_at->format('Y-m-d H:i:s'));
        $this->assertSame(150000, $invoice->amount);
    }

    public function test_timeline_rejects_invalid_days_and_times_then_updates_outstanding_deadlines(): void
    {
        $this->withoutMiddleware();
        $invoice = $this->invoice($this->customer());
        $this->postJson('/settings/billing', ['isolate_day' => 32, 'isolate_time' => '24:00'])->assertUnprocessable();
        $this->post('/settings/billing', ['isolate_day' => 15, 'isolate_time' => '09:30:00', 'invoice_issue_date' => 1])->assertRedirect();
        $this->assertDatabaseHas('settings', ['key' => 'isolate_day', 'value' => '15']);
        $this->assertSame('2026-10-15 02:30:00', $invoice->fresh()->isolation_at->format('Y-m-d H:i:s'));
    }
}
