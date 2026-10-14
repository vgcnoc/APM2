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

    // ── Relationships ──────────────────────────────────────────

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
