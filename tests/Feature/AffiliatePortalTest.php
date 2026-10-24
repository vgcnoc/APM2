<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AffiliatePortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        // Isolated fixtures: never run application migrations against the local database.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
            'cache.default' => 'array',
            'radius.enabled' => false,
        ]);
        DB::purge('sqlite');
        $this->withoutMiddleware(HandleInertiaRequests::class);
        $this->withoutVite();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'role'] as $column) {
                $table->string($column);
            }
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('base_salary')->default(0);
            $table->decimal('booking_fee')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'phone', 'position'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value');
        });
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('internet_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'customer_code', 'phone', 'address', 'area', 'referral_code', 'status', 'service_status', 'billing_type', 'billing_cycle'] as $column) {
                $table->string($column)->nullable();
            }
            foreach (['sales_id', 'area_id', 'package_id', 'billing_day'] as $column) {
                $table->unsignedBigInteger($column)->nullable();
            }
            $table->boolean('is_reseller')->default(false);
            $table->integer('base_amount')->default(0);
            $table->integer('installation_fee')->nullable();
            $table->date('registration_date')->nullable();
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('status');
        });
        Schema::create('incentives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->string('reference_id');
            $table->integer('amount');
        });
        Schema::create('master_fees', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->boolean('is_active');
            $table->integer('nominal');
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            foreach (['user_name', 'role', 'action', 'entity_type', 'status_before', 'status_after', 'notes', 'ip_address'] as $column) {
                $table->text($column)->nullable();
            }
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->timestamps();
        });
        (require base_path('database/migrations/2026_09_29_182032_create_permission_tables.php'))->up();
        (require base_path('database/migrations/2026_10_14_160000_create_affiliate_marketing_tables.php'))->up();
        DB::table('settings')->insert(['key' => 'affiliate_commission_booking', 'value' => '0']);
    }

    private function user(string $role = 'teknisi', bool $active = true): User
    {
        return User::create([
            'name' => 'User '.uniqid(), 'email' => uniqid().'@example.test',
            'password' => Hash::make('portal-test-password'), 'role' => $role, 'is_active' => $active,
        ]);
    }

    private function profile(User $user, ?User $upline = null): AffiliateProfile
    {
        return AffiliateProfile::create([
            'user_id' => $user->id, 'upline_id' => $upline?->id,
            'referral_code' => 'AFF-'.$user->id, 'status' => 'active',
        ]);
    }

    public function test_non_sales_employee_can_choose_affiliate_login_without_changing_job(): void
    {
        $user = $this->user('custom-warehouse');
        DB::table('employees')->insert(['email' => $user->email, 'position' => 'Gudang']);
        $this->withSession(['url.intended' => '/marketing/affiliates'])->post('/login', [
            'email' => $user->email, 'password' => 'portal-test-password', 'portal' => 'affiliate',
        ])->assertRedirect(route('affiliate.dashboard'))->assertSessionHas('login_portal', 'affiliate');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('custom-warehouse', $user->fresh()->role);
    }

    public function test_sales_login_always_opens_personal_dashboard(): void
    {
        $user = $this->user('sales');
        $this->withSession(['url.intended' => '/customers'])->post('/login', [
            'email' => $user->email, 'password' => 'portal-test-password', 'portal' => 'staff',
        ])->assertRedirect(route('affiliate.dashboard'))->assertSessionHas('login_portal', 'affiliate');
    }

    public function test_regular_staff_login_keeps_operational_access(): void
    {
        $user = $this->user();
        $this->post('/login', ['email' => $user->email, 'password' => 'portal-test-password', 'portal' => 'staff'])
            ->assertRedirect(route('dashboard'))->assertSessionHas('login_portal', 'staff');
    }

    public function test_inactive_and_customer_accounts_cannot_enter_affiliate_portal(): void
    {
        foreach ([$this->user('sales', false), $this->user('customer')] as $user) {
            $this->post('/login', ['email' => $user->email, 'password' => 'portal-test-password', 'portal' => 'affiliate'])
                ->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_affiliate_session_blocks_other_modules_even_for_an_admin(): void
    {
        $user = $this->user('admin');
        $this->actingAs($user)->withSession(['login_portal' => 'affiliate']);
        $this->get('/marketing/affiliates')->assertRedirect(route('affiliate.dashboard'));
        $this->post('/marketing/affiliates/settings', [])->assertForbidden();
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_dashboard_contains_only_own_bookings_commissions_and_two_downline_levels(): void
    {
        $owner = $this->user('sales');
        $other = $this->user('affiliate');
        $l1 = $this->user('affiliate');
        $l2 = $this->user('affiliate');
        $l3 = $this->user('affiliate');
        $this->profile($owner);
        $this->profile($other);
        $this->profile($l1, $owner);
        $this->profile($l2, $l1);
        $this->profile($l3, $l2);
        $customer = DB::table('customers')->insertGetId(['name' => 'Own booking', 'sales_id' => $owner->id, 'status' => 'booking']);
        DB::table('customers')->insert(['name' => 'Unrelated booking', 'sales_id' => $other->id, 'status' => 'active']);
        foreach ([['booking', 35000], ['monthly_direct', 5000], ['clawback_booking', 10000]] as [$type, $amount]) {
            AffiliateCommission::create(['affiliate_id' => $owner->id, 'customer_id' => $customer, 'type' => $type, 'amount' => $amount, 'status' => 'payroll', 'description' => 'Own commission']);
        }
        AffiliateCommission::create(['affiliate_id' => $other->id, 'type' => 'booking', 'amount' => 999999, 'description' => 'Private']);
        $this->actingAs($owner)->get('/affiliate/dashboard?tab=downlines')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Affiliate/Dashboard')
                ->where('tab', 'downlines')->where('profile.user_id', $owner->id)
                ->where('kpi.total_customers', 1)->where('kpi.pending_bookings', 1)
                ->where('kpi.total_earned', 30000)->where('kpi.total_downlines', 2)
                ->has('customers', 1)->where('customers.0.name', 'Own booking')
                ->has('downlinesL1', 1)->where('downlinesL1.0.user_id', $l1->id)
                ->has('downlinesL2', 1)->where('downlinesL2.0.name', $l2->name)
                ->has('commissions.data', 3));
    }

    public function test_booking_is_always_attributed_to_login_user_and_paid_service(): void
    {
        $owner = $this->user();
        $other = $this->user('sales');
        DB::table('areas')->insert(['id' => 1, 'name' => 'Service Area']);
        DB::table('internet_packages')->insert(['id' => 1, 'name' => 'Home', 'price' => 150000]);
        $this->actingAs($owner)->withSession(['login_portal' => 'affiliate'])
            ->post('/affiliate/booking', [
                'name' => 'Personal booking', 'phone' => '08123456789', 'address' => 'Test address',
                'area' => 'Service Area', 'package_id' => 1, 'base_amount' => 150000,
                'registration_date' => '2026-10-24', 'sales_id' => $other->id,
                'referral_code' => 'FORGED', 'service_status' => 'gratis', 'is_reseller' => true,
            ])->assertSessionHasNoErrors()->assertRedirect(route('affiliate.dashboard'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Personal booking', 'sales_id' => $owner->id, 'status' => 'booking',
            'service_status' => 'berbayar', 'is_reseller' => false,
            'referral_code' => AffiliateProfile::where('user_id', $owner->id)->value('referral_code'),
        ]);
    }

    public function test_booking_form_exposes_only_own_sales_account_and_no_customer_address_suggestions(): void
    {
        $owner = $this->user();
        $this->user('sales');
        $this->actingAs($owner)->withSession(['login_portal' => 'affiliate'])
            ->get('/affiliate/booking/create')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Customers/Create')
                ->where('affiliatePortal', true)->has('sales', 1)->where('sales.0.id', $owner->id)
                ->has('area_villages', 0));
    }

    public function test_suspended_profile_cannot_make_new_booking(): void
    {
        $owner = $this->user('affiliate');
        $this->profile($owner)->update(['status' => 'suspended']);
        $this->actingAs($owner)->get('/affiliate/booking/create')->assertForbidden();
        $this->post('/affiliate/booking', [])->assertForbidden();
    }

    public function test_commission_pagination_keeps_personal_scope_and_selected_tab(): void
    {
        $owner = $this->user('affiliate');
        $this->profile($owner);
        for ($i = 0; $i < 16; $i++) {
            AffiliateCommission::create(['affiliate_id' => $owner->id, 'type' => 'booking', 'amount' => 1000, 'description' => 'Commission '.$i]);
        }
        $this->actingAs($owner)->get('/affiliate/dashboard?tab=commissions&commissions_page=2')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Affiliate/Dashboard')
                ->where('tab', 'commissions')->has('commissions.data', 1)
                ->where('commissions.total', 16)->where('commissions.current_page', 2)
                ->where('commissions.prev_page_url', fn ($url) => str_contains($url, 'tab=commissions')));
    }

    public function test_shared_portal_props_do_not_expose_staff_directory(): void
    {
        $owner = $this->user('sales');
        $this->withMiddleware(HandleInertiaRequests::class);
        $this->actingAs($owner)->get('/affiliate/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Affiliate/Dashboard')
                ->where('auth.user.id', $owner->id)->where('auth.user.affiliate_portal', true)
                ->has('officers', 0));
    }
}
