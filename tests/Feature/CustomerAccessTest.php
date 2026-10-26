<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Customer;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CustomerAccessService;
use App\Services\RadiusService;
use App\Services\RouterOsApiClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'radius.connection' => 'sqlite', 'radius.enabled' => true, 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        Process::fake();
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('role'); $t->integer('area_id')->nullable(); });
        Schema::create('areas', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('customer_code')->default('TEST'); $t->string('status')->default('active');
            $t->string('service_status')->default('berbayar'); $t->integer('area_id')->default(1); $t->integer('package_id')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_26_190000_add_connection_access_to_customers.php'))->up();
        Schema::create('onts', function (Blueprint $t) {
            $t->id(); $t->integer('customer_id'); $t->boolean('free_hotspot')->default(false);
            foreach (['pppoe_user', 'pppoe_password', 'hotspot_user', 'hotspot_password'] as $c) $t->string($c)->nullable();
        });
        Schema::create('internet_packages', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('vouchers', function (Blueprint $t) {
            $t->id(); $t->integer('customer_id'); $t->string('username'); $t->string('password')->nullable();
            $t->boolean('is_free')->default(true); $t->boolean('is_active')->default(true); $t->string('status')->default('used');
        });
        foreach (['radcheck', 'radreply'] as $table) Schema::create($table, function (Blueprint $t) {
            $t->id(); $t->string('username'); $t->string('attribute'); $t->string('op'); $t->string('value');
        });
        Schema::create('radusergroup', function (Blueprint $t) { $t->id(); $t->string('username'); $t->string('groupname'); $t->integer('priority'); });
        Schema::create('routers', function (Blueprint $t) { $t->id(); $t->integer('area_id'); $t->integer('nas_id'); $t->integer('api_port'); });
        Schema::create('nas', function (Blueprint $t) {
            $t->increments('id'); foreach (['nasname', 'api_user', 'api_password', 'secret'] as $c) $t->string($c)->nullable();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->integer('user_id')->nullable(); $t->integer('entity_id')->nullable();
            foreach (['user_name', 'role', 'action', 'entity_type', 'status_before', 'status_after', 'notes', 'ip_address'] as $c) $t->text($c)->nullable();
            $t->timestamps();
        });
        (require base_path('database/migrations/2026_09_29_182032_create_permission_tables.php'))->up();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        DB::table('nas')->insert(['id' => 1, 'nasname' => '10.9.0.10', 'api_user' => 'test', 'api_password' => 'test']);
        DB::table('routers')->insert(['area_id' => 1, 'nas_id' => 1, 'api_port' => 18728]);
        DB::table('internet_packages')->insert(['id' => 1, 'name' => 'Package']);
    }

    private function customer(): Customer
    {
        $id = DB::table('customers')->insertGetId(['name' => 'Test Customer', 'package_id' => 1]);
        DB::table('onts')->insert(['customer_id' => $id, 'pppoe_user' => 'alice', 'pppoe_password' => 'secret']);
        return Customer::findOrFail($id);
    }

    private function client(bool $offline = false): RouterOsApiClient
    {
        $client = new class($offline) extends RouterOsApiClient {
            public array $calls = [];
            public bool $denyRemove = false;
            public array $records = [['.id' => '*1', 'name' => 'alice', 'disabled' => 'false'], ['.id' => '*2', 'name' => 'other', 'disabled' => 'false']];
            public function __construct(private bool $offline) {}
            public function connect(string $host, int $port, string $user, string $password, float $timeout = 1.5): void {
                if ($this->offline) throw new \RuntimeException('Offline');
                $this->calls[] = ['connect', $host, $port];
            }
            public function close(): void {}
            public function command(string $path, array $params = []): array {
                $this->calls[] = [$path, $params];
                if ($this->denyRemove && str_ends_with($path, '/remove')) throw new \RuntimeException('Not enough permissions');
                if ($path === '/ppp/secret/print') return ['rows' => array_values(array_filter($this->records, fn ($r) => $r['name'] === $params['?name']))];
                if ($path === '/ppp/secret/set') foreach ($this->records as &$record) {
                    if ($record['.id'] === $params['.id']) $record['disabled'] = $params['disabled'] === 'yes' ? 'true' : 'false';
                }
                if ($path === '/ppp/active/print') return ['rows' => [['.id' => '*A', 'name' => 'alice'], ['.id' => '*B', 'name' => 'other']]];
                return ['rows' => []];
            }
        };
        $this->app->instance(RouterOsApiClient::class, $client);
        return $client;
    }

    public function test_disable_rejects_login_and_only_disables_and_disconnects_matching_local_customer(): void
    {
        $customer = $this->customer();
        $client = $this->client();
        $result = app(CustomerAccessService::class)->setDisabled($customer, true);
        $this->assertTrue($result['complete']);
        $this->assertTrue($customer->fresh()->connection_disabled);
        $this->assertFalse($customer->fresh()->connection_access_pending);
        $this->assertSame('active', $customer->fresh()->status);
        $this->assertDatabaseHas('radcheck', ['username' => 'alice', 'attribute' => 'Auth-Type', 'value' => 'Reject']);
        $this->assertContains(['/ppp/secret/set', ['.id' => '*1', 'disabled' => 'yes']], $client->calls);
        $this->assertContains(['/ppp/active/remove', ['.id' => '*A']], $client->calls);
        $this->assertNotContains(['/ppp/active/remove', ['.id' => '*B']], $client->calls);
        $this->assertSame('false', $client->records[1]['disabled']);
        $this->assertLessThan(array_search(['/ppp/active/remove', ['.id' => '*A']], $client->calls), array_search(['/ppp/secret/set', ['.id' => '*1', 'disabled' => 'yes']], $client->calls));
    }

    public function test_enable_restores_only_accounts_disabled_by_this_feature_and_respects_isolir(): void
    {
        $customer = $this->customer();
        $client = $this->client();
        app(CustomerAccessService::class)->setDisabled($customer, true);
        DB::table('customers')->where('id', $customer->id)->update(['status' => 'suspended']);
        app(CustomerAccessService::class)->setDisabled($customer, false);
        $this->assertSame('false', $client->records[0]['disabled']);
        $this->assertFalse($customer->fresh()->connection_disabled);
        $this->assertDatabaseHas('radusergroup', ['username' => 'alice', 'groupname' => 'isolir']);
        $this->assertDatabaseMissing('radcheck', ['username' => 'alice', 'attribute' => 'Auth-Type']);
        $this->assertEmpty($customer->fresh()->connection_disable_state);
    }

    public function test_an_already_disabled_local_account_is_not_reenabled(): void
    {
        $customer = $this->customer(); $client = $this->client(); $client->records[0]['disabled'] = 'true';
        app(CustomerAccessService::class)->setDisabled($customer, true);
        app(CustomerAccessService::class)->setDisabled($customer, false);
        $this->assertSame('true', $client->records[0]['disabled']);
        $this->assertNotContains(['/ppp/secret/set', ['.id' => '*1', 'disabled' => 'no']], $client->calls);
    }

    public function test_radius_customer_and_voucher_resync_cannot_reactivate_disabled_credentials(): void
    {
        $customer = $this->customer();
        DB::table('customers')->where('id', $customer->id)->update(['connection_disabled' => true]);
        DB::table('onts')->where('customer_id', $customer->id)->update(['hotspot_user' => 'bob']);
        DB::table('vouchers')->insert(['customer_id' => $customer->id, 'username' => 'free-user']);
        $radius = app(RadiusService::class);
        $radius->syncCustomer($customer->fresh());
        $radius->syncVouchers(Voucher::all());
        foreach (['alice', 'bob', 'free-user'] as $username) {
            $this->assertDatabaseHas('radcheck', ['username' => $username, 'attribute' => 'Auth-Type', 'value' => 'Reject']);
            $this->assertDatabaseMissing('radcheck', ['username' => $username, 'attribute' => 'Cleartext-Password']);
        }
    }

    public function test_unreachable_router_is_pending_and_is_not_falsely_reported_as_complete(): void
    {
        $customer = $this->customer(); $this->client(true);
        $result = app(CustomerAccessService::class)->setDisabled($customer, true);
        $this->assertFalse($result['complete']);
        $this->assertTrue($customer->fresh()->connection_access_pending);
        $this->assertTrue($customer->fresh()->connection_disabled);
        $this->assertDatabaseHas('radcheck', ['username' => 'alice', 'value' => 'Reject']);
        $this->client();
        $this->assertTrue(app(CustomerAccessService::class)->setDisabled($customer, true)['complete']);
        $this->assertFalse($customer->fresh()->connection_access_pending);
    }

    public function test_local_only_mode_does_not_claim_success_when_no_local_login_was_blocked(): void
    {
        config(['radius.enabled' => false]);
        $customer = $this->customer(); $client = $this->client(); $client->records = [];
        $this->assertFalse(app(CustomerAccessService::class)->setDisabled($customer, true)['complete']);
    }

    public function test_radius_only_customer_can_be_disconnected_with_coa_when_api_is_read_only(): void
    {
        $customer = $this->customer(); $client = $this->client(); $client->records = []; $client->denyRemove = true;
        $radius = \Mockery::mock(RadiusService::class)->makePartial();
        $radius->shouldReceive('sendDisconnectToNas')->once()->withArgs(fn ($nas, $username) => $nas->id === 1 && $username === 'alice')->andReturn(true);
        $this->assertTrue((new CustomerAccessService($radius))->setDisabled($customer, true)['complete']);
        $this->assertDatabaseHas('radcheck', ['username' => 'alice', 'value' => 'Reject']);
    }

    public function test_route_requires_action_permission_and_area_scope_before_network_mutations(): void
    {
        $customer = $this->customer();
        $user = (new User)->forceFill(['id' => 10, 'name' => 'Staff', 'role' => 'cs', 'area_id' => 2]);
        $this->actingAs($user);
        $this->post('/customers/'.$customer->id.'/connection-access', ['disabled' => true])->assertForbidden();
        foreach (['customers_active_suspend', 'customers_all_view_area'] as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
        $this->post('/customers/'.$customer->id.'/connection-access', ['disabled' => true])->assertForbidden();
        $this->assertFalse($customer->fresh()->connection_disabled);
    }

    public function test_admin_action_validates_input_and_rejects_enable_on_terminated_customers(): void
    {
        $customer = $this->customer();
        $this->actingAs((new User)->forceFill(['id' => 1, 'role' => 'admin', 'name' => 'Admin']));
        $this->postJson('/customers/'.$customer->id.'/connection-access', ['disabled' => 'bad'])->assertUnprocessable();
        DB::table('customers')->where('id', $customer->id)->update(['status' => 'terminated', 'connection_disabled' => true]);
        $this->post('/customers/'.$customer->id.'/connection-access', ['disabled' => false])->assertSessionHas('error');
        $this->assertTrue($customer->fresh()->connection_disabled);
    }

    public function test_admin_disable_endpoint_persists_state_and_records_audit_without_exposing_restore_metadata(): void
    {
        $customer = $this->customer(); $this->client();
        $this->actingAs((new User)->forceFill(['id' => 1, 'role' => 'admin', 'name' => 'Admin']));
        $this->from('/customers')->post('/customers/'.$customer->id.'/connection-access', ['disabled' => true])
            ->assertRedirect('/customers')->assertSessionHas('success');
        $this->assertDatabaseHas('audit_logs', ['action' => 'Disable Koneksi', 'entity_id' => $customer->id]);
        $this->assertTrue($customer->fresh()->connection_disabled);
        $this->assertArrayNotHasKey('connection_disable_state', $customer->fresh()->toArray());
    }
}
