<?php

namespace Tests\Feature;

use App\Models\VoucherProfile;
use App\Models\Radius\RadGroupCheck;
use App\Models\Radius\RadGroupReply;
use App\Services\RadiusService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RadiusVoucherLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'radius.connection' => 'sqlite', 'radius.enabled' => true, 'cache.default' => 'array']);
        DB::purge('sqlite');
        foreach (['radgroupcheck', 'radgroupreply'] as $table) Schema::create($table, function (Blueprint $t) {
            $t->id(); $t->string('groupname'); $t->string('attribute'); $t->string('op'); $t->string('value');
        });
        Schema::create('voucher_profiles', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->integer('shared_users')->default(1); $t->boolean('is_customer_free')->default(false);
            $t->string('duration')->default('1h'); $t->string('limit_rate')->nullable(); $t->timestamps();
        });
    }

    private function assertLimit(VoucherProfile $profile, int $limit): void
    {
        app(RadiusService::class)->syncVoucherProfile($profile);
        $group = 'vcp_'.$profile->id;
        $this->assertSame((string) $limit, RadGroupCheck::where('groupname', $group)->where('attribute', 'Simultaneous-Use')->value('value'));
        $this->assertSame((string) $limit, RadGroupReply::where('groupname', $group)->where('attribute', 'Port-Limit')->value('value'));
    }

    public function test_standard_voucher_enforces_limit_on_radius_and_router(): void
    {
        $this->assertLimit((new VoucherProfile)->forceFill(['id' => 1, 'shared_users' => 1, 'duration' => '1h']), 1);
        $this->assertSame('3600', RadGroupCheck::where('attribute', 'Max-All-Session')->value('value'));
    }

    public function test_paid_profiles_still_allow_the_configured_number_of_sessions(): void
    {
        $this->assertLimit((new VoucherProfile)->forceFill(['id' => 1, 'shared_users' => '3', 'duration' => '1h']), 3);
    }

    public function test_free_customer_profile_always_enforces_one_even_with_incorrect_legacy_data(): void
    {
        $this->assertLimit((new VoucherProfile)->forceFill(['id' => 1, 'shared_users' => 9, 'is_customer_free' => true, 'duration' => '1h']), 1);
        $this->assertSame(0, RadGroupCheck::where('attribute', 'Max-All-Session')->count());
        $this->assertLimit((new VoucherProfile)->forceFill(['id' => 2, 'shared_users' => 9, 'duration' => 'Mengikuti Pelanggan']), 1);
        $this->assertSame(0, RadGroupCheck::where('attribute', 'Max-All-Session')->count());
    }

    public function test_saving_free_profile_clamps_shared_users_before_observer_sync(): void
    {
        $profile = VoucherProfile::create(['name' => 'Free', 'shared_users' => 9, 'is_customer_free' => true]);
        $this->assertSame(1, $profile->fresh()->shared_users);
        $this->assertSame('1', RadGroupReply::where('groupname', 'vcp_'.$profile->id)->where('attribute', 'Port-Limit')->value('value'));
    }

    public function test_resynchronization_replaces_old_limits_without_duplicate_attributes(): void
    {
        $profile = (new VoucherProfile)->forceFill(['id' => 1, 'shared_users' => 3, 'duration' => '1h']);
        $this->assertLimit($profile, 3);
        $profile->shared_users = 1;
        $this->assertLimit($profile, 1);
        $this->assertSame(1, RadGroupReply::where('attribute', 'Port-Limit')->count());
        $this->assertSame(1, RadGroupCheck::where('attribute', 'Simultaneous-Use')->count());
    }

    public function test_sync_command_dry_run_is_read_only_and_apply_only_normalizes_free_limit(): void
    {
        DB::table('voucher_profiles')->insert([
            ['name' => 'Legacy Free', 'shared_users' => 8, 'is_customer_free' => true],
            ['name' => 'Paid', 'shared_users' => 3, 'is_customer_free' => false],
        ]);
        $this->artisan('radius:sync-voucher-limits')->assertSuccessful();
        $this->assertSame(8, VoucherProfile::findOrFail(1)->shared_users);
        $this->assertSame(0, RadGroupReply::count());
        $this->artisan('radius:sync-voucher-limits --apply')->assertSuccessful();
        $this->assertSame(1, VoucherProfile::findOrFail(1)->shared_users);
        $this->assertSame(3, VoucherProfile::findOrFail(2)->shared_users);
        $this->assertSame('1', RadGroupReply::where('groupname', 'vcp_1')->where('attribute', 'Port-Limit')->value('value'));
        $this->assertSame('3', RadGroupReply::where('groupname', 'vcp_2')->where('attribute', 'Port-Limit')->value('value'));
    }

    public function test_invalid_or_unset_paid_limit_does_not_become_unlimited(): void
    {
        foreach ([0, -1, null] as $value) {
            $this->assertLimit((new VoucherProfile)->forceFill(['id' => 1, 'shared_users' => $value, 'duration' => '1h']), 1);
        }
    }
}
