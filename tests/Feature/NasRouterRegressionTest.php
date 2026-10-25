<?php

namespace Tests\Feature;

use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Router;
use App\Services\NasConfigurationService;
use App\Services\NasManagementService;
use App\Services\RouterCapacityService;
use App\Services\RouterConnectionService;
use App\Services\RouterOsApiClient;
use App\Services\RouterTrafficService;
use App\Services\VpnAccountService;
use App\Support\NasAddress;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NasRouterRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        config([
            'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'radius.connection' => 'sqlite', 'cache.default' => 'array', 'session.driver' => 'array',
            'radius.enabled' => false, 'radius.vpn.enabled' => true, 'radius.nas_auto_apply' => true,
            'radius.vpn.pool_start' => '10.9.0.10', 'radius.vpn.pool_end' => '10.9.0.250',
            'radius.interim_interval' => 60, 'app.timezone' => 'UTC',
            'radius.check_command' => 'check-radius', 'radius.restart_command' => 'restart-radius',
            'radius.status_command' => 'status-radius',
        ]);
        DB::purge('sqlite');
        $this->withoutMiddleware();
        $this->withoutVite();
        Carbon::setTestNow(Carbon::parse('2026-10-25 05:00:00', 'UTC'));
        Schema::create('nas', function (Blueprint $t) {
            $t->increments('id');
            foreach (['nasname', 'shortname', 'type', 'secret', 'server', 'community', 'description', 'vpn_user', 'vpn_password', 'vpn_ip', 'api_user', 'api_password'] as $column) $t->string($column)->nullable();
            $t->unsignedInteger('ports')->nullable();
            $t->unsignedInteger('coa_port')->default(3799);
            $t->unique('vpn_ip');
        });
        Schema::create('areas', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('settings', function (Blueprint $t) { $t->id(); $t->string('key'); $t->text('value')->nullable(); });
        Schema::create('routers', function (Blueprint $t) {
            $t->id(); $t->string('name');
            foreach (['area_id', 'nas_id', 'pic_id', 'api_port', 'winbox_port'] as $column) $t->unsignedInteger($column)->nullable();
            $t->timestamps();
        });
        Schema::create('radacct', function (Blueprint $t) {
            $t->increments('radacctid'); $t->string('nasipaddress'); $t->string('username')->nullable();
            $t->string('framedipaddress')->nullable(); $t->string('callingstationid')->nullable();
            foreach (['acctstarttime', 'acctupdatetime', 'acctstoptime'] as $column) $t->dateTime($column)->nullable();
            $t->unsignedBigInteger('acctinputoctets')->default(0); $t->unsignedBigInteger('acctoutputoctets')->default(0);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function nas(array $attributes = []): Nas
    {
        return Nas::create(array_merge(['nasname' => '10.9.0.10', 'shortname' => 'Router A', 'secret' => 'test-secret', 'api_user' => 'test-api', 'api_password' => 'test-password'], $attributes));
    }

    private function traffic(array $measurements = []): RouterTrafficService
    {
        return new class($measurements) extends RouterTrafficService {
            public function __construct(private array $measurements) {}
            protected function probeRouter(Nas $nas): array { return $this->measurements[$nas->id] ?? ['status' => 'offline', 'traffic_available' => false]; }
        };
    }

    public function test_router_creation_allocates_unique_specific_nas_addresses(): void
    {
        $this->post('/routers', ['name' => 'Router A'])->assertRedirect('/routers');
        $this->post('/routers', ['name' => 'Router B'])->assertRedirect('/routers');
        $this->assertSame(['10.9.0.10', '10.9.0.11'], Nas::orderBy('id')->pluck('nasname')->all());
        $this->assertSame(['10.9.0.10', '10.9.0.11'], Nas::orderBy('id')->pluck('vpn_ip')->all());
        $this->assertSame(2, Router::whereNotNull('nas_id')->count());
        $this->assertSame(2, Nas::pluck('vpn_user')->unique()->count());
        $this->assertSame(2, Nas::pluck('secret')->unique()->count());
    }

    public function test_direct_router_requires_ip_and_valid_ports(): void
    {
        config(['radius.vpn.enabled' => false]);
        $this->post('/routers', ['name' => 'No IP'])->assertSessionHasErrors('nasname');
        $this->post('/routers', ['name' => 'Bad port', 'nasname' => '192.0.2.1', 'api_port' => 65536])->assertSessionHasErrors('api_port');
        $this->post('/routers', ['name' => 'Valid', 'nasname' => '192.0.2.1', 'api_port' => 18728])->assertRedirect('/routers');
        $this->assertSame('192.0.2.1', Nas::first()->nasname);
        $this->assertNull(Nas::first()->vpn_ip);
    }

    public function test_explicit_nas_vpn_create_and_direct_wildcard_rejection(): void
    {
        $this->post('/radius/nas', ['shortname' => 'VPN', 'secret' => 'secret', 'use_vpn' => true])->assertRedirect('/radius/nas');
        $this->assertSame('10.9.0.10', Nas::first()->nasname);
        foreach (['0.0.0.0/0', '0.0.0.0', '10.9.0.0/24', '::/0'] as $address) {
            $this->post('/radius/nas', ['nasname' => $address, 'secret' => 'secret', 'use_vpn' => false])->assertSessionHasErrors('nasname');
        }
        $this->assertSame(1, Nas::count());
    }

    public function test_canonical_duplicate_is_rejected_and_pool_skips_direct_addresses(): void
    {
        $manager = app(NasManagementService::class);
        $manager->create(['nasname' => '10.9.0.10/32', 'secret' => 'test'], false);
        $vpn = $manager->create(['secret' => 'test'], true);
        $this->assertSame('10.9.0.11', $vpn->vpn_ip);
        $this->expectException(ValidationException::class);
        $manager->create(['nasname' => '10.9.0.10', 'secret' => 'test'], false);
    }

    public function test_vpn_edit_preserves_address_and_credentials(): void
    {
        $manager = app(NasManagementService::class);
        $nas = $manager->create(['secret' => 'test'], true);
        $before = $nas->only(['vpn_ip', 'vpn_user', 'vpn_password', 'api_user', 'api_password']);
        $manager->update($nas, ['shortname' => 'New name']);
        $this->assertSame($before, $nas->fresh()->only(array_keys($before)));
        $this->expectException(ValidationException::class);
        $manager->update($nas, ['nasname' => '10.9.0.20']);
    }

    public function test_allocator_handles_legacy_wildcard_without_reusing_its_vpn_address(): void
    {
        $this->nas(['nasname' => '0.0.0.0/0', 'vpn_ip' => '10.9.0.10']);
        $created = app(NasManagementService::class)->create(['secret' => 'test'], true);
        $this->assertSame('10.9.0.11', $created->nasname);
    }

    public function test_nas_index_is_read_only_and_search_includes_vpn_ip(): void
    {
        $this->withoutExceptionHandling();
        $legacy = $this->nas(['nasname' => '0.0.0.0/0', 'vpn_ip' => '10.9.0.10', 'api_user' => null, 'api_password' => null]);
        $this->nas(['nasname' => '192.0.2.5', 'shortname' => 'Other']);
        $vpn = $this->mock(VpnAccountService::class);
        $vpn->shouldNotReceive('sync'); $vpn->shouldNotReceive('prepareAccount');
        $this->get('/radius/nas?search=10.9.0.10')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Radius/Nas/Index')->has('nas.data', 1)->where('nas.data.0.id', $legacy->id));
        $this->assertNull($legacy->fresh()->api_user);
        $this->assertSame('0.0.0.0/0', $legacy->fresh()->nasname);
    }

    public function test_linked_nas_cannot_be_deleted_from_nas_menu(): void
    {
        $nas = $this->nas();
        Router::create(['name' => 'Linked', 'nas_id' => $nas->id]);
        $controller = app(\App\Http\Controllers\Radius\NasController::class);
        $controller->destroy($nas, app(NasManagementService::class));
        $this->assertSame(1, Nas::count());
        $this->assertSame(1, Router::count());
    }

    public function test_repair_is_dry_run_by_default_and_apply_backs_up_without_rotating_secrets(): void
    {
        Storage::fake('local');
        $a = $this->nas(['nasname' => '0.0.0.0/0', 'vpn_ip' => '10.9.0.10', 'vpn_password' => 'unchanged']);
        $b = $this->nas(['nasname' => '0.0.0.0/0', 'vpn_ip' => '10.9.0.11']);
        $this->artisan('radius:repair-nas')->assertSuccessful();
        $this->assertSame('0.0.0.0/0', $a->fresh()->nasname);
        $this->artisan('radius:repair-nas', ['--apply' => true])->assertSuccessful();
        $this->assertSame('10.9.0.10', $a->fresh()->nasname);
        $this->assertSame('10.9.0.11', $b->fresh()->nasname);
        $this->assertSame('unchanged', $a->fresh()->vpn_password);
        $files = Storage::disk('local')->allFiles('nas-backups');
        $this->assertCount(1, $files);
        $this->assertStringNotContainsString('password', Storage::disk('local')->get($files[0]));
    }

    public function test_unresolvable_repair_aborts_all_rows(): void
    {
        Storage::fake('local');
        $a = $this->nas(['nasname' => '0.0.0.0/0', 'vpn_ip' => '10.9.0.10']);
        $this->nas(['nasname' => '0.0.0.0/0']);
        $this->artisan('radius:repair-nas', ['--apply' => true])->assertFailed();
        $this->assertSame('0.0.0.0/0', $a->fresh()->nasname);
        $this->assertSame([], Storage::disk('local')->allFiles('nas-backups'));
    }

    public function test_unique_database_constraint_prevents_duplicates(): void
    {
        $this->nas();
        $migration = require base_path('database/migrations/2026_10_25_060000_enforce_unique_nas_address.php');
        $migration->up();
        $this->assertTrue(Schema::hasIndex('nas', 'nas_nasname_unique'));
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $this->nas();
    }

    public function test_zero_exit_duplicate_warning_does_not_restart_radius(): void
    {
        $this->nas();
        config(['radius.enabled' => true, 'radius.vpn.enabled' => false]);
        Process::fake(['check-radius' => Process::result(output: 'Failed to add duplicate client')]);
        $result = app(NasConfigurationService::class)->apply();
        $this->assertFalse($result['success']);
        Process::assertNotRan('restart-radius');
        $this->assertTrue(app(NasConfigurationService::class)->status()['pending']);
    }

    public function test_successful_apply_is_checked_before_restart_and_records_signature(): void
    {
        $this->nas();
        config(['radius.enabled' => true, 'radius.vpn.enabled' => false]);
        Process::fake(['check-radius' => Process::result(output: 'Configuration appears to be OK'), 'restart-radius' => Process::result(), 'status-radius' => Process::result(output: 'active')]);
        $service = app(NasConfigurationService::class);
        $this->assertTrue($service->apply()['success']);
        $this->assertFalse($service->status()['pending']);
        Process::assertRan('check-radius'); Process::assertRan('restart-radius');
        $this->assertTrue($service->apply()['success']);
        Process::assertRanTimes('restart-radius', 1);
    }

    public function test_wildcard_configuration_is_not_applied(): void
    {
        $this->nas(['nasname' => '0.0.0.0/0']);
        config(['radius.enabled' => true]); Process::fake();
        $this->assertFalse(app(NasConfigurationService::class)->apply()['success']);
        Process::assertNothingRan();
    }

    public function test_restart_failure_is_not_recorded_as_applied(): void
    {
        $this->nas(); config(['radius.enabled' => true, 'radius.vpn.enabled' => false]);
        Process::fake(['check-radius' => Process::result(), 'restart-radius' => Process::result(errorOutput: 'permission denied', exitCode: 1)]);
        $service = app(NasConfigurationService::class);
        $this->assertFalse($service->apply()['success']);
        $this->assertTrue($service->status()['pending']);
        Process::assertNotRan('status-radius');
    }

    public function test_search_does_not_include_closed_or_stale_sessions(): void
    {
        $rows = [
            ['username' => 'fresh', 'acctupdatetime' => now(), 'acctstoptime' => null],
            ['username' => 'closed', 'acctupdatetime' => now(), 'acctstoptime' => now()],
            ['username' => 'stale', 'acctupdatetime' => now()->subDay(), 'acctstoptime' => null],
        ];
        foreach ($rows as $row) DB::table('radacct')->insert($row + ['nasipaddress' => '10.9.0.10', 'framedipaddress' => '192.0.2.1']);
        $controller = app(\App\Http\Controllers\Radius\OnlineUserController::class);
        $request = \Illuminate\Http\Request::create('/radius/online-users', 'GET', ['search' => '192.0.2.1']);
        $request->headers->set('X-Inertia', 'true');
        $props = $controller->index($request)->toResponse($request)->getData(true);
        $this->assertCount(1, $props['props']['users']['data']);
        $this->assertSame('fresh', $props['props']['users']['data'][0]['username']);
    }

    public function test_unknown_session_never_disconnects_an_arbitrary_nas(): void
    {
        $this->nas(['nasname' => '0.0.0.0/0']);
        $service = $this->partialMock(\App\Services\RadiusService::class);
        $service->shouldNotReceive('sendDisconnectToNas');
        $this->assertFalse($service->disconnectSession(new RadAcct(['nasipaddress' => '192.0.2.99', 'username' => 'demo'])));
    }

    public function test_traffic_unavailable_is_not_zero_and_has_no_fake_chart(): void
    {
        $this->nas();
        $result = $this->traffic()->getRealTraffic();
        $this->assertNull($result['total_mbps']); $this->assertNull($result['total_gb_today']);
        $this->assertFalse($result['data_available']); $this->assertSame('unavailable', $result['source_type']);
        $this->assertSame([], $result['history']);
    }

    public function test_dashboard_keeps_unavailable_traffic_and_coverage_metadata(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->unsignedInteger('package_id')->nullable(); $t->unsignedInteger('area_id')->nullable();
            $t->string('status')->nullable(); $t->string('service_status')->nullable();
        });
        Schema::create('invoices', function (Blueprint $t) { $t->id(); $t->unsignedInteger('customer_id'); $t->string('status'); });
        $capacity = $this->partialMock(RouterCapacityService::class);
        $capacity->shouldReceive('detect')->andReturn(['max_capacity' => 1, 'source' => 'unknown', 'cpu_load' => null, 'devices' => []]);
        $result = app(\App\Http\Controllers\DashboardController::class)->getRealtimeData();
        $this->assertNull($result['traffic']['total_mbps']);
        $this->assertFalse($result['traffic']['data_available']);
        $this->assertSame('unavailable', $result['traffic']['coverage']);
        $this->assertSame('unavailable', $result['traffic']['daily_usage_status']);
    }

    public function test_all_routers_are_aggregated_and_scoped_history_is_isolated(): void
    {
        $a = $this->nas(); $b = $this->nas(['nasname' => '10.9.0.11']);
        $service = $this->traffic([$a->id => ['status' => 'online', 'traffic_available' => true, 'rx_mbps' => 1.2, 'tx_mbps' => 0.3], $b->id => ['status' => 'online', 'traffic_available' => true, 'rx_mbps' => 3.4, 'tx_mbps' => 0.1]]);
        $all = $service->getRealTraffic();
        $this->assertEquals(5, $all['total_mbps']); $this->assertSame('complete', $all['coverage']);
        $this->assertCount(1, $all['history']);
        $one = $service->getRealTraffic($a->id);
        $this->assertEquals(1.5, $one['total_mbps']); $this->assertEquals(1.5, $one['peak_mbps_today']);
        $this->assertEquals(1.2, $one['history'][0]['rx']);
        $denied = $service->getRealTraffic($b->id, [$a->id]);
        $this->assertSame(0, $denied['selected_nas_count']); $this->assertSame([], $denied['history']);
        $this->assertSame(0, $service->getRealTraffic('all', [])['selected_nas_count']);
        $this->assertSame(0, $service->getRealTraffic('invalid')['selected_nas_count']);
    }

    public function test_real_measured_zero_is_distinct_from_missing_data_and_partial_coverage(): void
    {
        $a = $this->nas(); $this->nas(['nasname' => '10.9.0.11']);
        $result = $this->traffic([$a->id => ['status' => 'online', 'traffic_available' => true, 'rx_mbps' => 0, 'tx_mbps' => 0]])->getRealTraffic();
        $this->assertTrue($result['data_available']); $this->assertEquals(0, $result['total_mbps']);
        $this->assertSame('partial', $result['coverage']); $this->assertCount(1, $result['history']);
    }

    public function test_legacy_vpn_accounting_uses_real_interval_not_poll_interval(): void
    {
        $nas = $this->nas(['nasname' => '0.0.0.0/0', 'vpn_ip' => '10.9.0.10']);
        $id = DB::table('radacct')->insertGetId(['nasipaddress' => '10.9.0.10', 'acctstarttime' => now()->subHour(), 'acctupdatetime' => now(), 'acctinputoctets' => 1000000, 'acctoutputoctets' => 2000000], 'radacctid');
        $service = $this->traffic();
        $this->assertNull($service->getRealTraffic($nas->id)['total_mbps']);
        Carbon::setTestNow(now()->addMinute());
        DB::table('radacct')->where('radacctid', $id)->update(['acctupdatetime' => now(), 'acctinputoctets' => 8500000, 'acctoutputoctets' => 17000000]);
        $measured = $service->getRealTraffic($nas->id);
        $this->assertEquals(2, $measured['download_mbps']); $this->assertEquals(1, $measured['upload_mbps']);
        Carbon::setTestNow(now()->addSeconds(5));
        $this->assertEquals(3, $service->getRealTraffic($nas->id)['total_mbps']);
        Carbon::setTestNow(now()->addMinutes(4));
        $this->assertNull($service->getRealTraffic($nas->id)['total_mbps']);
        $this->assertSame(0, RadAcct::online()->fresh()->count());
    }

    public function test_old_lifetime_counters_are_not_counted_as_todays_usage(): void
    {
        $nas = $this->nas();
        $id = DB::table('radacct')->insertGetId(['nasipaddress' => $nas->nasname, 'acctstarttime' => now()->subDays(3), 'acctupdatetime' => now(), 'acctoutputoctets' => 50 * 1073741824], 'radacctid');
        $service = $this->traffic();
        $this->assertNull($service->getRealTraffic()['total_gb_today']);
        DB::table('radacct')->where('radacctid', $id)->increment('acctoutputoctets', 1073741824);
        $usage = $service->getRealTraffic();
        $this->assertEquals(1, $usage['total_gb_today']); $this->assertSame('partial', $usage['daily_usage_status']);
        $this->assertEquals(1, $service->getRealTraffic()['total_gb_today']);
    }

    public function test_daily_usage_resets_at_jakarta_midnight_not_utc_midnight(): void
    {
        $nas = $this->nas();
        DB::table('radacct')->insert(['nasipaddress' => $nas->nasname, 'acctstarttime' => '2026-10-24 17:30:00', 'acctupdatetime' => '2026-10-24 18:00:00', 'acctstoptime' => '2026-10-24 18:00:00', 'acctoutputoctets' => 1073741824]);
        $this->assertEquals(1, $this->traffic()->getRealTraffic()['total_gb_today']);
    }

    public function test_area_permissions_fail_closed_for_missing_or_unauthorized_areas(): void
    {
        Router::create(['name' => 'A', 'area_id' => 1, 'nas_id' => 4]);
        Router::create(['name' => 'B', 'area_id' => 2, 'nas_id' => 5]);
        $capacity = app(RouterCapacityService::class);
        $this->assertSame([], $capacity->nasIdsForScope('all', false, []));
        $this->assertSame([], $capacity->nasIdsForScope(2, false, [1]));
        $this->assertSame([4], $capacity->nasIdsForScope('all', false, [1]));
        $this->assertNull($capacity->nasIdsForScope('all', true, []));
    }

    public function test_health_requires_api_authentication_not_only_a_tcp_connection(): void
    {
        $nas = $this->nas();
        $client = $this->mock(RouterOsApiClient::class);
        $client->shouldReceive('connect')->once()->andThrow(new \RuntimeException('RouterOS: invalid user name or password'));
        $client->shouldReceive('close')->once();
        $result = app(RouterConnectionService::class)->probe($nas);
        $this->assertSame('offline', $result['status']); $this->assertFalse($result['api_authenticated']);
        $this->assertSame('api_access_denied', $result['state']);
    }

    public function test_health_reports_success_only_after_resource_read(): void
    {
        $nas = $this->nas();
        $client = $this->mock(RouterOsApiClient::class);
        $client->shouldReceive('connect')->once();
        $client->shouldReceive('command')->with('/system/resource/print', ['.proplist' => 'uptime'])->once()->andReturn(['rows' => [['uptime' => '1d']]]);
        $client->shouldReceive('close')->once();
        $result = app(RouterConnectionService::class)->probe($nas);
        $this->assertSame('online', $result['status']); $this->assertTrue($result['api_authenticated']);
        $this->assertFalse($result['accounting_fresh']);
    }

    public function test_single_address_normalization(): void
    {
        $this->assertSame('10.9.0.10', NasAddress::ip(' 10.9.0.10/32 '));
        $this->assertSame('2001:db8::1', NasAddress::ip('2001:db8::1/128'));
        foreach (['0.0.0.0/0', '0.0.0.0', '10.9.0.0/24', '::', '0:0:0:0:0:0:0:0', '::/0', 'router.local', null] as $ip) $this->assertNull(NasAddress::ip($ip));
    }
}
