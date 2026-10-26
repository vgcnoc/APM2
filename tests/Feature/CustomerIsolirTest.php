<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerIsolirTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'radius.connection' => 'sqlite', 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role'); $t->integer('area_id')->nullable();
            $t->string('phone')->nullable(); $t->boolean('is_on_duty')->default(false);
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('customer_code')->default('TEST'); $t->string('status');
            $t->string('service_status')->default('berbayar'); $t->boolean('connection_disabled')->default(false);
            $t->integer('area_id')->default(1); $t->integer('package_id')->nullable();
            $t->integer('sales_id')->nullable(); $t->integer('user_id')->nullable(); $t->date('registration_date')->nullable();
            foreach (['area', 'phone', 'email', 'address'] as $column) $t->string($column)->nullable();
            $t->timestamps();
        });
        Schema::create('areas', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('internet_packages', function (Blueprint $t) { $t->id(); $t->string('name'); $t->boolean('is_active')->default(true); });
        Schema::create('odps', function (Blueprint $t) { $t->id(); $t->string('name'); $t->integer('area_id')->nullable(); $t->integer('odc_id')->nullable(); });
        Schema::create('odcs', function (Blueprint $t) { $t->id(); $t->integer('area_id')->nullable(); $t->integer('olt_id')->nullable(); });
        Schema::create('olts', function (Blueprint $t) { $t->id(); $t->integer('area_id')->nullable(); });
        Schema::create('onts', function (Blueprint $t) { $t->id(); $t->integer('customer_id'); $t->integer('odp_id')->nullable(); });
        Schema::create('surveys', function (Blueprint $t) { $t->id(); $t->integer('customer_id'); $t->integer('surveyor_id')->nullable(); });
        Schema::create('technician_schedules', function (Blueprint $t) { $t->id(); $t->integer('customer_id'); $t->integer('technician_id'); });
        (require base_path('database/migrations/2026_09_29_182032_create_permission_tables.php'))->up();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        DB::table('areas')->insert([['id' => 1, 'name' => 'Utara'], ['id' => 2, 'name' => 'Selatan']]);
        foreach (['suspended', 'active', 'booking', 'terminated'] as $status) {
            DB::table('customers')->insert(['name' => $status, 'status' => $status]);
        }
        DB::table('customers')->insert(['name' => 'Disabled manual', 'status' => 'active', 'connection_disabled' => true]);
        $this->actingAs((new User)->forceFill(['id' => 1, 'name' => 'Admin', 'role' => 'admin']));
    }

    public function test_isolir_page_only_lists_isolated_customers_not_active_disabled_or_terminated(): void
    {
        $this->getJson('/customers/isolir', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('component', 'Customers/Index')->assertJsonPath('props.isolirOnly', true)
            ->assertJsonPath('props.customers.total', 1)->assertJsonPath('props.customers.data.0.status', 'suspended')
            ->assertJsonPath('props.filters.status', 'suspended')->assertJsonPath('props.statusOptions.suspended', 'Isolir');
        $this->assertSame('ISOLIR', Customer::where('status', 'suspended')->firstOrFail()->status_label);
    }

    public function test_changing_status_or_search_cannot_escape_the_isolir_list(): void
    {
        $this->getJson('/customers/isolir?status=active', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.customers.total', 1)->assertJsonPath('props.customers.data.0.status', 'suspended');
        $this->getJson('/customers/isolir?search=Disabled', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.customers.total', 0);
    }

    public function test_search_filters_and_pagination_keep_the_dedicated_route(): void
    {
        DB::table('customers')->insert(['name' => 'suspended second', 'status' => 'suspended', 'area_id' => 1]);
        $response = $this->getJson('/customers/isolir?search=suspended&area_id=1&per_page=1&sort_by=nama_asc', ['X-Inertia' => 'true']);
        $response->assertOk()->assertJsonPath('props.customers.total', 2)->assertJsonPath('props.customers.per_page', 1);
        $this->assertStringContainsString('/customers/isolir?', $response->json('props.customers.next_page_url'));
        $this->getJson($response->json('props.customers.next_page_url'), ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.customers.current_page', 2)->assertJsonPath('props.customers.data.0.status', 'suspended');
    }

    public function test_area_scoped_staff_cannot_see_isolated_customers_in_other_areas(): void
    {
        DB::table('customers')->insert(['name' => 'Other area', 'status' => 'suspended', 'area_id' => 2]);
        DB::table('users')->insert(['id' => 2, 'name' => 'Staff', 'role' => 'cs', 'area_id' => 1]);
        $user = User::findOrFail(2);
        foreach (['menu_customers_all', 'customers_all_view_area'] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($user)->getJson('/customers/isolir', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.customers.total', 1)->assertJsonPath('props.customers.data.0.area_id', 1);
    }

    public function test_isolir_page_requires_customer_menu_permission(): void
    {
        $this->actingAs((new User)->forceFill(['id' => 3, 'name' => 'Staff', 'role' => 'cs']))
            ->getJson('/customers/isolir', ['X-Inertia' => 'true'])->assertForbidden();
    }

    public function test_all_customers_page_remains_unrestricted_by_isolir_filter(): void
    {
        $this->getJson('/customers', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('props.isolirOnly', false)->assertJsonPath('props.customers.total', 4);
    }
}
