<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SpecialAttendanceController;
use App\Models\Area;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\SpecialAttendance;
use App\Models\SpecialTeam;
use App\Models\SpecialTeamMember;
use App\Models\WorkSchedule;
use App\Services\AttendanceCalculatorService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceShiftCheckoutTest extends TestCase
{
    private string $dummyPhoto = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'app.timezone' => 'Asia/Jakarta',
        ]);
        date_default_timezone_set('Asia/Jakarta');
        DB::purge('sqlite');
        Storage::fake('public');

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('areas', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('address')->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->integer('radius_meters')->default(200);
            $t->timestamps();
        });

        Schema::create('shifts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('start_time');
            $t->string('end_time');
            $t->boolean('is_night_shift')->default(false);
            $t->string('color')->nullable();
            $t->string('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('position')->nullable();
            $t->string('branch')->nullable();
            $t->string('iak_number')->nullable();
            $t->string('status')->default('active');
            $t->integer('area_id')->nullable();
            $t->integer('shift_id')->nullable();
            $t->text('allowed_attendance_areas')->nullable();
            $t->date('join_date')->nullable();
            $t->date('resign_date')->nullable();
            $t->integer('base_salary')->default(0);
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password')->default('x');
            $t->string('role')->default('staff');
            $t->boolean('is_on_duty')->default(false);
            $t->timestamps();
        });

        Schema::create('work_schedules', function (Blueprint $t) {
            $t->id();
            $t->integer('employee_id');
            $t->date('date');
            $t->integer('shift_id')->nullable();
            $t->string('status')->default('work');
            $t->string('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->integer('employee_id');
            $t->integer('work_schedule_id')->nullable();
            $t->integer('shift_id')->nullable();
            $t->integer('area_id')->nullable();
            $t->string('attendance_type')->default('wfo');
            $t->date('date');
            $t->string('clock_in')->nullable();
            $t->string('clock_out')->nullable();
            $t->string('photo_in')->nullable();
            $t->string('photo_out')->nullable();
            $t->decimal('lat_in', 10, 7)->nullable();
            $t->decimal('lng_in', 10, 7)->nullable();
            $t->decimal('lat_out', 10, 7)->nullable();
            $t->decimal('lng_out', 10, 7)->nullable();
            $t->integer('distance_in')->nullable();
            $t->integer('distance_out')->nullable();
            $t->string('status')->default('present');
            $t->integer('late_minutes')->default(0);
            $t->text('notes')->nullable();
            $t->string('duty_location')->nullable();
            $t->boolean('is_verified')->default(true);
            $t->boolean('is_synced_to_payroll')->default(false);
            $t->timestamps();
        });

        Schema::create('special_teams', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->string('start_time')->default('08:30:00');
            $t->string('end_time')->default('18:00:00');
            $t->integer('tolerance_minutes')->default(15);
            $t->boolean('without_radius')->default(true);
            $t->decimal('late_deduction_amount', 12, 2)->default(25000);
            $t->decimal('no_out_deduction_amount', 12, 2)->default(25000);
            $t->decimal('alpha_deduction_amount', 12, 2)->default(35000);
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('special_team_members', function (Blueprint $t) {
            $t->id();
            $t->integer('special_team_id');
            $t->integer('employee_id');
            $t->string('role')->default('Anggota');
            $t->boolean('is_leader')->default(false);
            $t->timestamps();
        });

        Schema::create('special_attendances', function (Blueprint $t) {
            $t->id();
            $t->integer('special_team_id');
            $t->integer('employee_id');
            $t->date('date');
            $t->string('clock_in')->nullable();
            $t->string('clock_out')->nullable();
            $t->string('photo_in')->nullable();
            $t->string('photo_out')->nullable();
            $t->decimal('lat_in', 10, 7)->nullable();
            $t->decimal('lng_in', 10, 7)->nullable();
            $t->decimal('lat_out', 10, 7)->nullable();
            $t->decimal('lng_out', 10, 7)->nullable();
            $t->string('location_in')->nullable();
            $t->string('location_out')->nullable();
            $t->string('status')->default('present');
            $t->integer('late_minutes')->default(0);
            $t->integer('early_leave_minutes')->default(0);
            $t->text('notes')->nullable();
            $t->boolean('is_synced_to_payroll')->default(false);
            $t->timestamps();
        });

        Setting::set('attendance_regular_require_photo', '0');
        Setting::set('attendance_regular_require_gps', '0');
        Setting::set('attendance_regular_strict_radius', '0');
        Setting::set('attendance_checkout_auto_lock', '1');
        Setting::set('attendance_checkout_deadline_mode', 'shift_relative');
        Setting::set('attendance_checkout_deadline_hours', '4');
        Setting::set('attendance_checkout_deadline_time', '18:30');
        Setting::set('attendance_checkout_min_work_hours', '4');
        Setting::set('attendance_checkout_early_tolerance', '15');
        Setting::set('attendance_special_deadline_time', '19:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_morning_shift_checkout_follows_shift_end_plus_4_hours(): void
    {
        $shift = Shift::create(['name' => 'Shift 1-Teknisi', 'start_time' => '08:35', 'end_time' => '17:10', 'is_night_shift' => false]);
        $emp = Employee::create(['name' => 'Andi Teknisi', 'shift_id' => $shift->id]);
        $controller = new AttendanceController();

        Carbon::setTestNow('2026-11-01 08:30:00');
        $controller->clockIn(Request::create('/attendances/clock-in', 'POST', ['employee_id' => $emp->id]));

        // Pukul 21:05 WIB (masih sebelum 17:10 + 4 jam = 21:10 WIB) -> Berhasil absen pulang!
        Carbon::setTestNow('2026-11-01 21:05:00');
        $controller->clockOut(Request::create('/attendances/clock-out', 'POST', ['employee_id' => $emp->id]));

        $att = Attendance::where('employee_id', $emp->id)->first();
        $this->assertEquals('21:05:00', $att->clock_out);
        $this->assertStringNotContainsString('Pulang lebih awal', (string) $att->notes);
    }

    public function test_shift_2_evening_until_2300_can_checkout_even_after_midnight_and_under_before_cron_mode(): void
    {
        // Bahkan jika mode 'before_cron' dengan cut-off 18:30, Shift 2-CS (15:00 - 23:00) otomatis diberi +4 jam (s/d 03:00 H+1)!
        Setting::set('attendance_checkout_deadline_mode', 'before_cron');
        Setting::set('attendance_checkout_deadline_time', '18:30');

        $shift2 = Shift::create(['name' => 'Shift 2-CS', 'start_time' => '15:00', 'end_time' => '23:00', 'is_night_shift' => false]);
        $emp = Employee::create(['name' => 'Rina CS', 'shift_id' => $shift2->id]);
        $controller = new AttendanceController();

        Carbon::setTestNow('2026-11-01 14:55:00');
        $controller->clockIn(Request::create('/attendances/clock-in', 'POST', ['employee_id' => $emp->id]));

        // Pukul 00:20 WIB dini hari esoknya (masih sebelum 03:00 WIB) -> Berhasil absen pulang tanpa denda pulang awal!
        Carbon::setTestNow('2026-11-02 00:20:00');
        $controller->clockOut(Request::create('/attendances/clock-out', 'POST', ['employee_id' => $emp->id]));

        $att = Attendance::where('employee_id', $emp->id)->where('date', '2026-11-01')->first();
        $this->assertNotNull($att);
        $this->assertEquals('00:20:00', $att->clock_out);
        $this->assertStringNotContainsString('Pulang lebih awal', (string) $att->notes);
    }

    public function test_cross_midnight_night_shift_not_auto_closed_at_2330_and_checks_out_next_morning(): void
    {
        $nightShift = Shift::create(['name' => 'Shift Malam', 'start_time' => '23:00', 'end_time' => '08:00', 'is_night_shift' => true]);
        $emp = Employee::create(['name' => 'Budi NOC', 'shift_id' => $nightShift->id]);
        $controller = new AttendanceController();

        // Masuk jam 22:55 tanggal 01 Nov
        Carbon::setTestNow('2026-11-01 22:55:00');
        $controller->clockIn(Request::create('/attendances/clock-in', 'POST', ['employee_id' => $emp->id]));

        // Cron AutoEvaluate jalan pukul 23:30 WIB -> Shift Malam TIDAK boleh di-auto-close!
        Carbon::setTestNow('2026-11-01 23:30:00');
        Artisan::call('attendance:auto-evaluate');

        $attAfterCron = Attendance::where('employee_id', $emp->id)->first();
        $this->assertStringNotContainsString('Auto-Closed', (string) $attAfterCron->notes);
        $this->assertFalse(AttendanceCalculatorService::isMissingClockOut($attAfterCron, Carbon::now()));

        // Keesokan paginya tanggal 02 Nov pukul 08:05 WIB -> Berhasil absen pulang!
        Carbon::setTestNow('2026-11-02 08:05:00');
        $controller->clockOut(Request::create('/attendances/clock-out', 'POST', ['employee_id' => $emp->id]));

        $attAfterOut = Attendance::where('employee_id', $emp->id)->first();
        $this->assertEquals('2026-11-01', $attAfterOut->date->format('Y-m-d'));
        $this->assertEquals('08:05:00', $attAfterOut->clock_out);
        $this->assertStringNotContainsString('Pulang lebih awal', (string) $attAfterOut->notes);

        // Malamnya pukul 22:58 pada tanggal 02 Nov -> Bisa absen masuk lagi untuk Shift Malam tanggal 02 Nov!
        Carbon::setTestNow('2026-11-02 22:58:00');
        $controller->clockIn(Request::create('/attendances/clock-in', 'POST', ['employee_id' => $emp->id]));
        $this->assertEquals(2, Attendance::where('employee_id', $emp->id)->count());
    }

    public function test_cross_midnight_shift_late_arrival_after_midnight_and_locked_after_deadline(): void
    {
        $nightShift = Shift::create(['name' => 'Shift Malam', 'start_time' => '23:00', 'end_time' => '08:00', 'is_night_shift' => true]);
        $emp = Employee::create(['name' => 'Dedi NOC', 'shift_id' => $nightShift->id]);
        WorkSchedule::create(['employee_id' => $emp->id, 'date' => '2026-11-01', 'shift_id' => $nightShift->id, 'status' => 'work']);
        WorkSchedule::create(['employee_id' => $emp->id, 'date' => '2026-11-02', 'shift_id' => $nightShift->id, 'status' => 'work']);

        $controller = new AttendanceController();

        // Terlambat masuk pukul 00:20 WIB (02 Nov) untuk shift malam 01 Nov (23:00) -> tercatat pada 01 Nov, telat 80 menit!
        Carbon::setTestNow('2026-11-02 00:20:00');
        $controller->clockIn(Request::create('/attendances/clock-in', 'POST', ['employee_id' => $emp->id]));

        $att = Attendance::where('employee_id', $emp->id)->first();
        $this->assertEquals('2026-11-01', $att->date->format('Y-m-d'));
        $this->assertEquals('late', $att->status);
        $this->assertEquals(80, $att->late_minutes);

        // Coba pulang pukul 02:00 WIB (baru kerja 1 jam 40 menit < 4 jam) -> Ditolak!
        Carbon::setTestNow('2026-11-02 02:00:00');
        $controller->clockOut(Request::create('/attendances/clock-out', 'POST', ['employee_id' => $emp->id]));
        $this->assertNull($att->fresh()->clock_out);

        // Coba pulang pukul 12:15 WIB siang (melewati batas 08:00 + 4 jam = 12:00 WIB) -> Ditolak otomatis (Lupa Absen Pulang)!
        Carbon::setTestNow('2026-11-02 12:15:00');
        $controller->clockOut(Request::create('/attendances/clock-out', 'POST', ['employee_id' => $emp->id]));
        $this->assertNull($att->fresh()->clock_out);
        $this->assertTrue(AttendanceCalculatorService::isMissingClockOut($att->fresh(), Carbon::now()));
    }

    public function test_special_team_checkout_deadline_1900_and_not_penalized_while_working(): void
    {
        $team = SpecialTeam::create([
            'name' => 'GRUP TIM KHUSUS',
            'code' => 'TK-01',
            'start_time' => '08:30:00',
            'end_time' => '18:00:00',
            'tolerance_minutes' => 15,
        ]);
        $emp = Employee::create(['name' => 'Eko Lapangan']);
        SpecialTeamMember::create(['special_team_id' => $team->id, 'employee_id' => $emp->id]);

        $specialCtrl = new SpecialAttendanceController();

        Carbon::setTestNow('2026-11-01 08:35:00');
        $specialCtrl->clockIn(Request::create('/absen-khusus/clock-in', 'POST', [
            'special_team_id' => $team->id,
            'employee_id' => $emp->id,
            'photo' => $this->dummyPhoto,
        ]));

        $sAtt = SpecialAttendance::first();
        $this->assertNotNull($sAtt);

        // Pukul 11:00 siang saat masih bekerja -> BUKAN Tanpa Absen Pulang!
        Carbon::setTestNow('2026-11-01 11:00:00');
        $this->assertFalse(AttendanceCalculatorService::isSpecialMissingClockOut($sAtt, $team, Carbon::now()));

        // Pukul 19:05 WIB (lewat batas 19:00 WIB) -> Ditolak & dihitung Tanpa Absen Pulang!
        Carbon::setTestNow('2026-11-01 19:05:00');
        $specialCtrl->clockOut(Request::create('/absen-khusus/clock-out', 'POST', [
            'special_team_id' => $team->id,
            'employee_id' => $emp->id,
            'photo' => $this->dummyPhoto,
        ]));

        $this->assertNull($sAtt->fresh()->clock_out);
        $this->assertTrue(AttendanceCalculatorService::isSpecialMissingClockOut($sAtt->fresh(), $team, Carbon::now()));
    }
}

