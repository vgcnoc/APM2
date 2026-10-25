<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Customer;
use App\Models\Radius\Nas;
use App\Models\User;
use App\Services\CustomerConnectionService;
use App\Services\RouterOsApiClient;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NetworkMapConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'radius.connection' => 'sqlite', 'radius.enabled' => false, 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        Carbon::setTestNow(Carbon::parse('2026-10-25T12:00:00Z'));
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('role'); $t->integer('area_id')->nullable(); });
        Schema::create('areas', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('status')->default('active'); $t->string('area')->nullable();
            $t->integer('area_id')->nullable(); $t->integer('sales_id')->nullable(); $t->decimal('latitude', 12, 8)->nullable(); $t->decimal('longitude', 12, 8)->nullable();
        });
        Schema::create('onts', function (Blueprint $t) { $t->id(); $t->integer('customer_id'); $t->string('pppoe_user')->nullable(); $t->string('hotspot_user')->nullable(); });
        Schema::create('routers', function (Blueprint $t) { $t->id(); $t->integer('area_id')->nullable(); $t->integer('nas_id'); $t->integer('api_port')->nullable(); });
        Schema::create('nas', function (Blueprint $t) {
            $t->increments('id');
            foreach (['nasname', 'vpn_ip', 'api_user', 'api_password'] as $c) $t->string($c)->nullable();
        });
        Schema::create('radacct', function (Blueprint $t) {
            $t->increments('radacctid'); $t->string('username'); $t->string('nasipaddress');
            foreach (['acctstarttime', 'acctupdatetime', 'acctstoptime'] as $c) $t->dateTime($c)->nullable();
        });
        (require base_path('database/migrations/2026_09_29_182032_create_permission_tables.php'))->up();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        DB::table('areas')->insert([['id' => 1, 'name' => 'Area A'], ['id' => 2, 'name' => 'Area B']]);
        DB::table('nas')->insert([
            ['id' => 1, 'nasname' => '10.9.0.10', 'api_user' => 'api-test', 'api_password' => 'never-expose'],
            ['id' => 2, 'nasname' => '10.9.0.11', 'api_user' => 'api-test', 'api_password' => 'never-expose'],
        ]);
        DB::table('routers')->insert([
            ['area_id' => 1, 'nas_id' => 1, 'api_port' => 18728],
            ['area_id' => 2, 'nas_id' => 2, 'api_port' => 8728],
        ]);
    }

    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    private function customer(int $area = 1, ?string $ppp = 'alice', ?string $hotspot = null): Customer
    {
        $id = DB::table('customers')->insertGetId(['name' => 'Pelanggan Uji', 'area_id' => $area, 'latitude' => -6.9, 'longitude' => 106.7]);
        DB::table('onts')->insert(['customer_id' => $id, 'pppoe_user' => $ppp, 'hotspot_user' => $hotspot]);
        return Customer::with('ont')->findOrFail($id);
    }

    private function sample(array $ppp = [], array $hotspot = [], bool $pppOk = true, bool $hotspotOk = true): array
    {
        return ['readable' => ['ppp' => $pppOk, 'hotspot' => $hotspotOk],
            'users' => ['ppp' => array_fill_keys($ppp, true), 'hotspot' => array_fill_keys($hotspot, true)],
            'checked_at' => now()->toIso8601String()];
    }

    private function service(array $samples): CustomerConnectionService
    {
        return new class($samples) extends CustomerConnectionService {
            public array $calls = [];
            public function __construct(private array $samples) {}
            protected function readSessions(Nas $nas, int $port): array {
                $this->calls[] = [$nas->id, $port];
                return $this->samples[$nas->id] ?? ['readable' => ['ppp' => false, 'hotspot' => false], 'users' => ['ppp' => [], 'hotspot' => []], 'checked_at' => now()->toIso8601String()];
            }
        };
    }

    public function test_api_sessions_are_matched_to_the_customer_area_nas_and_custom_port(): void
    {
        $a = $this->customer(1, 'shared-name');
        $b = $this->customer(2, 'shared-name');
        $service = $this->service([1 => $this->sample(), 2 => $this->sample(['shared-name'])]);
        $rows = $service->snapshot(collect([$a, $b]))['customers'];
        $this->assertSame(['offline', 'online'], array_column($rows, 'connection_status'));
        $this->assertSame([[1, 18728], [2, 8728]], $service->calls);
    }

    public function test_multiple_customers_share_one_api_sample_and_hotspot_is_supported(): void
    {
        $a = $this->customer();
        $b = $this->customer(1, null, 'hotspot-bob');
        $service = $this->service([1 => $this->sample(['alice'], ['hotspot-bob'])]);
        $rows = $service->snapshot(collect([$a, $b]))['customers'];
        $this->assertSame(['online', 'online'], array_column($rows, 'connection_status'));
        $this->assertCount(1, $service->calls);
    }

    public function test_unreachable_or_partially_readable_router_is_unknown_not_offline(): void
    {
        $customer = $this->customer(1, 'alice', 'bob');
        $this->assertSame('unknown', $this->service([])->snapshot(collect([$customer]))['customers'][0]['connection_status']);
        $this->assertSame('unknown', $this->service([1 => $this->sample([], [], true, false)])->snapshot(collect([$customer]))['customers'][0]['connection_status']);
    }

    public function test_missing_mapping_and_missing_usernames_remain_unknown_even_for_active_subscription(): void
    {
        $a = $this->customer(3);
        $b = $this->customer(1, null);
        $rows = $this->service([1 => $this->sample(['alice'])])->snapshot(collect([$a, $b]))['customers'];
        $this->assertSame(['unknown', 'unknown'], array_column($rows, 'connection_status'));
    }

    public function test_one_unreachable_candidate_prevents_a_false_offline_but_positive_api_is_online(): void
    {
        DB::table('routers')->insert(['area_id' => 1, 'nas_id' => 2, 'api_port' => 8728]);
        $customer = $this->customer();
        $this->assertSame('unknown', $this->service([1 => $this->sample()])->snapshot(collect([$customer]))['customers'][0]['connection_status']);
        $this->assertSame('online', $this->service([1 => $this->sample(), 2 => $this->sample(['alice'])])->snapshot(collect([$customer]))['customers'][0]['connection_status']);
    }

    public function test_only_fresh_matching_radius_can_fallback_when_api_is_unavailable(): void
    {
        $customer = $this->customer();
        $id = DB::table('radacct')->insertGetId(['username' => 'alice', 'nasipaddress' => '10.9.0.10', 'acctupdatetime' => now(), 'acctstoptime' => null]);
        $row = $this->service([])->snapshot(collect([$customer]))['customers'][0];
        $this->assertSame('online', $row['connection_status']);
        $this->assertSame('radius', $row['connection_source']);
        DB::table('radacct')->where('radacctid', $id)->update(['nasipaddress' => '10.9.0.11']);
        $this->assertSame('unknown', $this->service([])->snapshot(collect([$customer]))['customers'][0]['connection_status']);
        DB::table('radacct')->where('radacctid', $id)->update(['nasipaddress' => '10.9.0.10', 'acctupdatetime' => now()->subMinutes(2)]);
        $this->assertSame('unknown', $this->service([])->snapshot(collect([$customer]))['customers'][0]['connection_status']);
    }

    public function test_successful_empty_api_read_overrides_an_old_radius_online_record(): void
    {
        $customer = $this->customer();
        DB::table('radacct')->insert(['username' => 'alice', 'nasipaddress' => '10.9.0.10', 'acctupdatetime' => now()]);
        $this->assertSame('offline', $this->service([1 => $this->sample()])->snapshot(collect([$customer]))['customers'][0]['connection_status']);
    }

    public function test_latest_accounting_nas_can_map_customers_without_an_area_router(): void
    {
        $customer = $this->customer(3);
        DB::table('radacct')->insert(['username' => 'alice', 'nasipaddress' => '10.9.0.11', 'acctupdatetime' => now()->subDay(), 'acctstoptime' => now()->subDay()]);
        $this->assertSame('online', $this->service([2 => $this->sample(['alice'])])->snapshot(collect([$customer]))['customers'][0]['connection_status']);
    }

    public function test_real_api_reader_uses_read_only_commands_and_caches_the_sample(): void
    {
        $a = $this->customer();
        $client = $this->mock(RouterOsApiClient::class);
        $client->shouldReceive('connect')->once()->with('10.9.0.10', 18728, 'api-test', 'never-expose', 1.0);
        $client->shouldReceive('command')->once()->with('/ppp/active/print', ['.proplist' => 'name'])->andReturn(['rows' => [['name' => 'alice']]]);
        $client->shouldReceive('command')->once()->with('/ip/hotspot/active/print', ['.proplist' => 'user'])->andReturn(['rows' => []]);
        $client->shouldReceive('close')->once();
        $service = app(CustomerConnectionService::class);
        $this->assertSame('online', $service->snapshot(collect([$a]))['customers'][0]['connection_status']);
        $this->assertSame('online', $service->snapshot(collect([$a]))['customers'][0]['connection_status']);
    }

    public function test_endpoint_requires_login_permission_and_respects_customer_scope(): void
    {
        $this->getJson('/network-map/connections')->assertUnauthorized();
        $user = new User(['name' => 'Staf Area', 'role' => 'cs', 'area_id' => 1]);
        $user->timestamps = false;
        $user->save();
        $this->actingAs($user)->getJson('/network-map/connections')->assertForbidden();
        foreach (['menu_network_map', 'customers_all_view_area'] as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
        $allowed = $this->customer(1);
        $hidden = $this->customer(2);
        $this->mock(CustomerConnectionService::class)->shouldReceive('snapshot')->once()
            ->withArgs(fn (Collection $customers) => $customers->pluck('id')->all() === [$allowed->id])
            ->andReturn(['customers' => [['id' => $allowed->id, 'connection_status' => 'unknown']]]);
        $response = $this->getJson('/network-map/connections')->assertOk()->assertJsonCount(1, 'customers');
        $response->assertJsonMissing(['id' => $hidden->id]);
        $this->assertStringNotContainsString('never-expose', $response->getContent());
    }
}
