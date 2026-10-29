<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'area_id',
        'accessible_areas',
        'is_active',
        'is_on_duty',
        'base_salary',
        'incentive_rate',
        'booking_fee',
        'installation_fee',
        'bpjs_kesehatan',
        'bpjs_kesehatan_no',
        'bpjs_ketenagakerjaan',
        'bpjs_tk_no',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'last_latitude',
        'last_longitude',
        'last_location_name',
        'last_location_accuracy',
        'last_gps_updated_at',
        'last_seen_at',
        'is_gps_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_on_duty' => 'boolean',
            'accessible_areas' => 'array',
            'bpjs_kesehatan' => 'boolean',
            'bpjs_ketenagakerjaan' => 'boolean',
            'last_latitude' => 'float',
            'last_longitude' => 'float',
            'last_location_accuracy' => 'float',
            'last_gps_updated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_gps_enabled' => 'boolean',
        ];
    }

    protected $appends = [
        'job_position',
        'is_currently_online',
        'online_status',
    ];

    protected static ?array $empPosCache = null;
    protected static ?array $spatieFirstRoleCache = null;

    protected static function booted(): void
    {
        $clearCaches = function (self $u) {
            self::$empPosCache = null;
            self::$spatieFirstRoleCache = null;
            try {
                \Illuminate\Support\Facades\Cache::forget('inertia_on_duty_officers');
                \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);
            } catch (\Throwable $e) {}
        };
        static::saved($clearCaches);
        static::deleted($clearCaches);
    }

    protected static function ensureJobPositionLookupLoaded(): void
    {
        if (self::$empPosCache === null) {
            self::$empPosCache = ['email' => [], 'phone' => [], 'name' => []];
            try {
                $rows = \Illuminate\Support\Facades\DB::table('employees')
                    ->whereNotNull('position')
                    ->where('position', '!=', '')
                    ->get(['email', 'phone', 'name', 'position']);
                foreach ($rows as $r) {
                    $pos = trim((string) $r->position);
                    if ($pos === '') continue;
                    if (!empty($r->email)) {
                        $k = mb_strtolower(trim((string) $r->email));
                        self::$empPosCache['email'][$k] ??= $pos;
                    }
                    if (!empty($r->phone)) {
                        $k = trim((string) $r->phone);
                        self::$empPosCache['phone'][$k] ??= $pos;
                    }
                    if (!empty($r->name)) {
                        $k = mb_strtolower(trim((string) $r->name));
                        self::$empPosCache['name'][$k] ??= $pos;
                    }
                }
            } catch (\Throwable $e) {}
        }

        if (self::$spatieFirstRoleCache === null) {
            self::$spatieFirstRoleCache = [];
            try {
                $roleRows = \Illuminate\Support\Facades\DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', static::class)
                    ->get(['model_has_roles.model_id', 'roles.name']);
                foreach ($roleRows as $rr) {
                    self::$spatieFirstRoleCache[$rr->model_id] ??= $rr->name;
                }
            } catch (\Throwable $e) {}
        }
    }

    public function getIsCurrentlyOnlineAttribute(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gte(now()->subMinutes(5));
    }

    public function getOnlineStatusAttribute(): string
    {
        if ($this->last_seen_at && $this->last_seen_at->gte(now()->subMinutes(5))) {
            return 'online';
        }
        if ($this->last_seen_at && $this->last_seen_at->gte(now()->subMinutes(30))) {
            return 'idle';
        }
        return 'offline';
    }

    public function locationLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeLocationLog::class, 'user_id');
    }

    // ── Relationships ──────────────────────────────────────────

    public function employee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Employee::class, 'email', 'email');
    }

    /**
     * Dapatkan posisi/jabatan riil karyawan (dari Employee HR) atau Spatie role / role sistem
     */
    public function getJobPositionAttribute(): string
    {
        // 1. Cek dari relasi data Karyawan (HR) tanpa N+1 query
        if ($this->relationLoaded('employee') && $this->employee && !empty($this->employee->position)) {
            return trim($this->employee->position);
        }

        self::ensureJobPositionLookupLoaded();

        $pos = null;
        if (!empty($this->email)) {
            $pos = self::$empPosCache['email'][mb_strtolower(trim((string) $this->email))] ?? null;
        }
        if (!$pos && !empty($this->phone)) {
            $pos = self::$empPosCache['phone'][trim((string) $this->phone)] ?? null;
        }
        if (!$pos && !empty($this->name)) {
            $pos = self::$empPosCache['name'][mb_strtolower(trim((string) $this->name))] ?? null;
        }
        if (!empty($pos)) {
            return $pos;
        }

        // 2. Cek dari Spatie Roles tanpa N+1 query
        $spatieRole = $this->relationLoaded('roles')
            ? $this->roles->first()?->name
            : (self::$spatieFirstRoleCache[$this->id] ?? null);
        if ($spatieRole && !in_array(strtolower($spatieRole), ['cs', 'karyawan'])) {
            return ucwords(str_replace(['_', '-'], ' ', $spatieRole));
        }

        // 3. Cek dari kolom role bawaan jika bukan 'cs'
        if (!empty($this->role) && !in_array(strtolower($this->role), ['cs', 'karyawan'])) {
            return ucwords(str_replace(['_', '-'], ' ', $this->role));
        }

        if ($spatieRole) {
            return ucwords(str_replace(['_', '-'], ' ', $spatieRole));
        }

        return $this->role ? ucwords(str_replace(['_', '-'], ' ', $this->role)) : 'Karyawan';
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function affiliateProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AffiliateProfile::class);
    }

    public function affiliateCommissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'affiliate_id');
    }

    public function getAccessibleAreaIds(): array
    {
        $areas = array_filter((array) ($this->accessible_areas ?? []));
        if ($this->area_id && !in_array($this->area_id, $areas)) {
            $areas[] = $this->area_id;
        }
        return array_values(array_unique(array_map('intval', $areas)));
    }

    /**
     * Dapatkan level scope data pengguna: 'all' | 'area' | 'own'
     */
    public function getDataScope(string $module = 'customers_all'): string
    {
        return \App\Services\DataScopeService::getScopeLevel($this, $module);
    }

    public function isOwnDataOnly(string $module = 'customers_all'): bool
    {
        return $this->getDataScope($module) === 'own';
    }

    public function isAreaScoped(string $module = 'customers_all'): bool
    {
        return $this->getDataScope($module) === 'area';
    }

    public function isAllAreaScoped(string $module = 'customers_all'): bool
    {
        return $this->getDataScope($module) === 'all';
    }

    public function technicianSchedules(): HasMany
    {
        return $this->hasMany(TechnicianSchedule::class, 'technician_id');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class, 'surveyor_id');
    }

    public function voucherProfiles()
    {
        return $this->belongsToMany(VoucherProfile::class, 'reseller_voucher_profiles', 'user_id', 'voucher_profile_id')
            ->select('voucher_profiles.*');
    }

    // ── Helpers ────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeknisi(): bool
    {
        return $this->role === 'teknisi';
    }

    public function isNoc(): bool
    {
        return $this->role === 'noc';
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    public function reseller(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Reseller::class, 'user_id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function incentives(): HasMany
    {
        return $this->hasMany(Incentive::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(Deduction::class);
    }
}
