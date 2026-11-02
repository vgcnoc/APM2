<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Survey;
use App\Services\SurveyNetworkService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SurveyNetworkAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('customer_code'); $t->integer('area_id');
            $t->string('status'); $t->date('activation_date')->nullable(); $t->timestamps();
        });
        Schema::create('odps', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->integer('area_id'); $t->integer('total_ports');
            $t->integer('used_ports'); $t->string('status'); $t->timestamps();
        });
        Schema::create('odp_ports', function (Blueprint $t) {
            $t->id(); $t->integer('odp_id'); $t->integer('port_number'); $t->string('status');
            $t->string('description')->nullable(); $t->timestamps();
        });
        Schema::create('onts', function (Blueprint $t) {
            $t->id(); $t->integer('customer_id'); $t->integer('odp_id')->nullable();
            $t->integer('odp_port_id')->nullable(); $t->integer('port_number')->nullable(); $t->string('status');
            $t->string('pppoe_user')->nullable(); $t->string('pppoe_password')->nullable(); $t->timestamps();
        });
        Schema::create('surveys', function (Blueprint $t) {
            $t->id(); $t->integer('customer_id'); $t->integer('odp_id')->nullable();
            $t->integer('port_number')->nullable(); $t->string('feasibility');
            $t->string('notes')->nullable(); $t->timestamp('rolled_back_at')->nullable(); $t->integer('surveyor_id')->nullable();
            $t->decimal('distance_meters')->nullable(); $t->string('photos')->nullable(); $t->timestamps();
        });
        DB::table('customers')->insert([
            ['id' => 1, 'name' => 'Test customer', 'customer_code' => 'TEST-1', 'area_id' => 3, 'status' => 'active', 'activation_date' => '2026-11-02'],
            ['id' => 2, 'name' => 'Other customer', 'customer_code' => 'TEST-2', 'area_id' => 3, 'status' => 'survey', 'activation_date' => null],
        ]);
        DB::table('odps')->insert([
            ['id' => 1, 'name' => 'Old ODP', 'area_id' => 3, 'total_ports' => 8, 'used_ports' => 1, 'status' => 'full'],
            ['id' => 2, 'name' => 'New ODP', 'area_id' => 3, 'total_ports' => 8, 'used_ports' => 0, 'status' => 'active'],
        ]);
        DB::table('odp_ports')->insert([
            ['id' => 11, 'odp_id' => 1, 'port_number' => 5, 'status' => 'used'],
            ['id' => 21, 'odp_id' => 2, 'port_number' => 1, 'status' => 'available'],
        ]);
        DB::table('onts')->insert([
            'id' => 1, 'customer_id' => 1, 'odp_id' => 1, 'odp_port_id' => 11, 'port_number' => 5,
            'status' => 'active', 'pppoe_user' => 'test-user', 'pppoe_password' => 'test-password',
        ]);
        DB::table('surveys')->insert(['id' => 1, 'customer_id' => 1, 'odp_id' => 1, 'port_number' => 5, 'feasibility' => 'feasible']);
    }

    private function save(array $attributes = []): Survey
    {
        return app(SurveyNetworkService::class)->save(Customer::findOrFail(1), Survey::findOrFail(1), $attributes);
    }

    public function test_changing_survey_moves_existing_ont_without_reactivation_or_credential_changes(): void
    {
        $this->save(['odp_id' => 2, 'port_number' => 1]);
        $this->assertDatabaseHas('surveys', ['id' => 1, 'odp_id' => 2, 'port_number' => 1]);
        $this->assertDatabaseHas('onts', ['id' => 1, 'odp_id' => 2, 'port_number' => 1, 'odp_port_id' => 21,
            'pppoe_user' => 'test-user', 'pppoe_password' => 'test-password', 'status' => 'active']);
        $this->assertDatabaseHas('customers', ['id' => 1, 'status' => 'active', 'activation_date' => '2026-11-02']);
        $this->assertDatabaseHas('odp_ports', ['id' => 11, 'status' => 'available']);
        $this->assertDatabaseHas('odp_ports', ['id' => 21, 'status' => 'used']);
        $this->assertDatabaseHas('odps', ['id' => 1, 'used_ports' => 0, 'status' => 'active']);
        $this->assertDatabaseHas('odps', ['id' => 2, 'used_ports' => 1]);
        $this->assertDatabaseCount('onts', 1);
    }

    public function test_preinstallation_customer_gets_reservation_without_creating_ont(): void
    {
        DB::table('onts')->delete();
        DB::table('customers')->where('id', 1)->update(['status' => 'survey']);
        DB::table('odp_ports')->where('id', 11)->update(['status' => 'reserved']);
        $this->save(['odp_id' => 2, 'port_number' => 1]);
        $this->assertDatabaseHas('odp_ports', ['id' => 11, 'status' => 'available']);
        $this->assertDatabaseHas('odp_ports', ['id' => 21, 'status' => 'reserved']);
        $this->assertDatabaseCount('onts', 0);
    }

    public function test_existing_inconsistent_survey_can_be_reconciled_to_its_own_reserved_port(): void
    {
        DB::table('surveys')->where('id', 1)->update(['odp_id' => 2, 'port_number' => 1]);
        DB::table('odp_ports')->where('id', 21)->update(['status' => 'reserved']);
        $this->save();
        $this->assertDatabaseHas('onts', ['id' => 1, 'odp_id' => 2, 'port_number' => 1]);
        $this->assertDatabaseHas('odp_ports', ['id' => 21, 'status' => 'used']);
    }

    public function test_ont_still_in_stock_reserves_port_instead_of_claiming_installation(): void
    {
        DB::table('onts')->where('id', 1)->update(['status' => 'in_stock']);
        $this->save(['odp_id' => 2, 'port_number' => 1]);
        $this->assertDatabaseHas('onts', ['id' => 1, 'status' => 'in_stock', 'odp_id' => 2, 'port_number' => 1]);
        $this->assertDatabaseHas('odp_ports', ['id' => 21, 'status' => 'reserved']);
        $this->assertDatabaseHas('odps', ['id' => 2, 'used_ports' => 0]);
    }

    public function test_another_ont_blocks_the_move_even_when_port_flag_is_stale(): void
    {
        DB::table('onts')->insert(['id' => 2, 'customer_id' => 2, 'odp_id' => 2, 'port_number' => 1, 'status' => 'active']);
        $this->assertMoveRejected();
    }

    public function test_another_pending_survey_blocks_the_move_even_when_port_flag_is_available(): void
    {
        DB::table('surveys')->insert(['id' => 2, 'customer_id' => 2, 'odp_id' => 2, 'port_number' => 1, 'feasibility' => 'feasible']);
        $this->assertMoveRejected();
    }

    public function test_blocked_port_is_not_overwritten(): void
    {
        DB::table('odp_ports')->where('id', 21)->update(['status' => 'fault']);
        $this->assertMoveRejected();
        $this->assertDatabaseHas('odp_ports', ['id' => 21, 'status' => 'fault']);
    }

    public function test_own_used_port_can_be_saved_without_creating_a_second_assignment(): void
    {
        $this->save(['notes' => 'Correction']);
        $this->assertDatabaseHas('onts', ['id' => 1, 'odp_id' => 1, 'port_number' => 5]);
        $this->assertDatabaseHas('odp_ports', ['id' => 11, 'status' => 'used']);
        $this->assertDatabaseCount('onts', 1);
    }

    public function test_old_port_is_not_freed_if_another_ont_still_occupies_it(): void
    {
        DB::table('onts')->insert(['id' => 2, 'customer_id' => 2, 'odp_id' => 1, 'port_number' => 5, 'status' => 'active']);
        $this->save(['odp_id' => 2, 'port_number' => 1]);
        $this->assertDatabaseHas('odp_ports', ['id' => 11, 'status' => 'used']);
        $this->assertDatabaseHas('odps', ['id' => 1, 'used_ports' => 1]);
    }

    public function test_port_outside_capacity_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->save(['odp_id' => 2, 'port_number' => 9]);
    }

    public function test_explicit_null_network_does_not_fall_back_to_old_assignment(): void
    {
        $this->expectException(ValidationException::class);
        $this->save(['odp_id' => null, 'port_number' => null]);
    }

    public function test_survey_edit_endpoint_preserves_activated_status_and_updates_activation_network(): void
    {
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('role'); });
        Schema::create('technician_schedules', function (Blueprint $t) {
            $t->id(); $t->integer('customer_id'); $t->integer('technician_id'); $t->string('type');
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->integer('user_id')->nullable();
            foreach (['user_name','role','action','entity_type','status_before','status_after','notes','ip_address'] as $column) {
                $t->text($column)->nullable();
            }
            $t->integer('entity_id')->nullable(); $t->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'role' => 'admin']);
        $user = \App\Models\User::findOrFail(1);
        $user->setRelation('roles', collect([new \Spatie\Permission\Models\Role(['name' => 'admin', 'guard_name' => 'web'])]));
        $this->withoutMiddleware(\App\Http\Middleware\HandleInertiaRequests::class);
        $this->actingAs($user)->from('/customers/survey')->post('/customers/1/update-survey', [
            'surveyor_id' => 1, 'odp_id' => 2, 'port_number' => 1, 'distance_meters' => 100, 'feasibility' => 'feasible',
        ])->assertSessionHasNoErrors()->assertRedirect('/customers/survey');
        $customer = Customer::with('ont.odp')->findOrFail(1);
        $this->assertSame('active', $customer->status);
        $this->assertSame(2, (int) $customer->ont->odp_id);
        $this->assertSame('New ODP', $customer->ont->odp->name);
        $this->assertSame(1, $customer->ont->port_number);
    }

    private function assertMoveRejected(): void
    {
        try {
            $this->save(['odp_id' => 2, 'port_number' => 1]);
            $this->fail('Conflicting port must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('port_number', $e->errors());
        }
        $this->assertDatabaseHas('surveys', ['id' => 1, 'odp_id' => 1, 'port_number' => 5]);
        $this->assertDatabaseHas('onts', ['id' => 1, 'odp_id' => 1, 'port_number' => 5]);
        $this->assertDatabaseHas('odp_ports', ['id' => 11, 'status' => 'used']);
    }
}
