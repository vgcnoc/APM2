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
            $t->string('status'); $t->string('area')->default('Test area');
            $t->date('activation_date')->nullable(); $t->timestamps();
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
            $t->date('survey_date')->nullable(); $t->decimal('signal_loss')->nullable(); $t->boolean('port_available')->nullable();
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
        $this->authenticateSurveyAdmin();
        $before = (array) DB::table('customers')->find(1);
        $this->from('/customers/survey')->post('/customers/1/update-survey', $this->reportPayload())
            ->assertSessionHasNoErrors()->assertRedirect('/customers/survey');
        $customer = Customer::with('ont.odp')->findOrFail(1);
        $this->assertSame('active', $customer->status);
        $this->assertSame(2, (int) $customer->ont->odp_id);
        $this->assertSame('New ODP', $customer->ont->odp->name);
        $this->assertSame(1, $customer->ont->port_number);
        $this->assertSame($before, (array) DB::table('customers')->find(1));
    }

    public static function preservedLifecycleStatuses(): array
    {
        return array_map(fn ($status) => [$status], ['active', 'suspended', 'installing', 'terminated', 'survey']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('preservedLifecycleStatuses')]
    public function test_edit_never_resets_customer_lifecycle(string $status): void
    {
        $this->authenticateSurveyAdmin();
        DB::table('customers')->where('id', 1)->update(['status' => $status]);
        $before = (array) DB::table('customers')->find(1);
        $this->post('/customers/1/update-survey', $this->reportPayload())->assertSessionHasNoErrors();
        $this->assertSame($before, (array) DB::table('customers')->find(1));
        $this->assertDatabaseHas('onts', ['id' => 1, 'odp_id' => 2, 'port_number' => 1, 'status' => 'active']);
        $this->assertDatabaseHas('audit_logs', ['status_before' => $status, 'status_after' => $status]);
    }

    public function test_inline_report_edit_also_syncs_without_reactivation(): void
    {
        $this->authenticateSurveyAdmin();
        $before = (array) DB::table('customers')->find(1);
        $this->from('/customers/1')->post('/customers/1/update-survey-report', $this->reportPayload())
            ->assertSessionHasNoErrors()->assertRedirect('/customers/1');
        $this->assertSame($before, (array) DB::table('customers')->find(1));
        $this->assertDatabaseHas('onts', ['id' => 1, 'odp_id' => 2, 'port_number' => 1, 'odp_port_id' => 21]);
        $this->assertDatabaseHas('odp_ports', ['id' => 11, 'status' => 'available']);
        $this->assertDatabaseHas('odp_ports', ['id' => 21, 'status' => 'used']);
    }

    public function test_first_report_for_existing_active_customer_does_not_require_installation_again(): void
    {
        $this->authenticateSurveyAdmin();
        DB::table('surveys')->delete();
        $before = (array) DB::table('customers')->find(1);
        $this->post('/customers/1/store-survey', $this->reportPayload())->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Status layanan tetap'));
        $this->assertSame($before, (array) DB::table('customers')->find(1));
        $this->assertDatabaseHas('onts', ['id' => 1, 'odp_id' => 2, 'port_number' => 1]);
        $this->assertDatabaseCount('surveys', 1);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('preservedLifecycleStatuses')]
    public function test_survey_schedule_does_not_roll_back_existing_lifecycle(string $status): void
    {
        $this->withoutExceptionHandling();
        $this->authenticateSurveyAdmin();
        DB::table('customers')->where('id', 1)->update(['status' => $status]);
        $before = (array) DB::table('customers')->find(1);
        $this->post('/customers/1/assign-survey', [
            'technician_ids' => [1], 'scheduled_date' => '2026-11-03', 'scheduled_time' => '09:00',
        ])->assertSessionHasNoErrors()->assertRedirect('/customers/survey');
        $this->assertSame($before, (array) DB::table('customers')->find(1));
        $this->assertDatabaseHas('technician_schedules', ['customer_id' => 1, 'status' => 'scheduled']);
        $this->assertDatabaseHas('audit_logs', ['status_before' => $status, 'status_after' => $status]);
    }

    public function test_booking_can_still_progress_to_survey(): void
    {
        $this->authenticateSurveyAdmin();
        DB::table('customers')->where('id', 1)->update(['status' => 'booking', 'activation_date' => null]);
        $this->post('/customers/1/assign-survey', [
            'technician_ids' => [1], 'scheduled_date' => '2026-11-03', 'scheduled_time' => '09:00',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['id' => 1, 'status' => 'survey', 'activation_date' => null]);
    }

    public function test_active_customer_cannot_be_terminated_by_survey_feasibility_edit(): void
    {
        $this->authenticateSurveyAdmin();
        $this->post('/customers/1/update-survey', array_replace($this->reportPayload(), ['feasibility' => 'not_feasible']))
            ->assertSessionHasErrors('feasibility');
        $this->assertDatabaseHas('customers', ['id' => 1, 'status' => 'active']);
        $this->assertDatabaseHas('surveys', ['id' => 1, 'feasibility' => 'feasible', 'odp_id' => 1]);
        $this->assertDatabaseHas('onts', ['id' => 1, 'odp_id' => 1, 'port_number' => 5]);
    }

    public function test_sync_installation_moves_ont_and_updates_survey_and_reconciles_ports(): void
    {
        $service = app(SurveyNetworkService::class);
        $customer = Customer::findOrFail(1);
        $ont = $service->syncInstallation($customer, [
            'odp_id' => 2,
            'port_number' => 1,
            'serial_number' => 'ONT-TEST-INST',
        ]);

        $this->assertDatabaseHas('onts', ['id' => 1, 'odp_id' => 2, 'port_number' => 1, 'odp_port_id' => 21]);
        $this->assertDatabaseHas('surveys', ['id' => 1, 'odp_id' => 2, 'port_number' => 1]);
        $this->assertDatabaseHas('odp_ports', ['id' => 11, 'status' => 'available']);
        $this->assertDatabaseHas('odp_ports', ['id' => 21, 'status' => 'used']);
        $this->assertDatabaseHas('odps', ['id' => 1, 'used_ports' => 0, 'status' => 'active']);
        $this->assertDatabaseHas('odps', ['id' => 2, 'used_ports' => 1]);
    }

    public function test_sync_installation_rejects_conflicting_port(): void
    {
        $service = app(SurveyNetworkService::class);
        $customer2 = Customer::findOrFail(2);
        
        try {
            // Customer 1 is already on ODP 1 Port 5
            $service->syncInstallation($customer2, [
                'odp_id' => 1,
                'port_number' => 5,
            ]);
            $this->fail('Conflicting port in syncInstallation must throw ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('port_number', $e->errors());
        }
    }

    private function reportPayload(): array
    {
        return ['surveyor_id' => 1, 'survey_date' => '2026-11-02', 'odp_id' => 2,
            'port_number' => 1, 'distance_meters' => 100, 'feasibility' => 'feasible'];
    }

    private function authenticateSurveyAdmin(): void
    {
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('role'); });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('guard_name'); $t->timestamps();
        });
        Schema::create('technician_schedules', function (Blueprint $t) {
            $t->id(); $t->integer('customer_id'); $t->integer('technician_id'); $t->string('type');
            $t->string('status'); $t->date('scheduled_date')->nullable(); $t->string('scheduled_time')->nullable();
            $t->integer('area_id')->nullable(); $t->string('notes')->nullable(); $t->timestamps();
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
        $this->actingAs($user);
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
