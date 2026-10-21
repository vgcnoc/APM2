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
        ];
    }

    protected $appends = [
        'job_position',
    ];

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
        // 1. Cek dari relasi data Karyawan (HR)
        $emp = $this->relationLoaded('employee') ? $this->employee : null;
        if (!$emp && !empty($this->email)) {
            $emp = Employee::where('email', $this->email)->first();
        }
        if (!$emp && !empty($this->phone)) {
            $emp = Employee::where('phone', $this->phone)->first();
        }
        if (!$emp && !empty($this->name)) {
            $emp = Employee::where('name', $this->name)->first();
        }

        if ($emp && !empty($emp->position)) {
            return trim($emp->position);
        }

        // 2. Cek dari Spatie Roles
        $spatieRole = method_exists($this, 'getRoleNames') ? $this->getRoleNames()->first() : null;
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
        $areas = (array) ($this->accessible_areas ?? []);
        if ($this->area_id && !in_array($this->area_id, $areas)) {
            $areas[] = $this->area_id;
        }
        return $areas;
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
        return $this->belongsToMany(VoucherProfile::class, 'reseller_voucher_profiles', 'user_id', 'voucher_profile_id');
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
