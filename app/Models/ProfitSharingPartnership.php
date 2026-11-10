<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitSharingPartnership extends Model
{
    use HasFactory;

    protected $table = 'profit_sharing_partnerships';

    protected $fillable = [
        'scheme_id',
        'partner_type',
        'area_id',
        'user_id',
        'partner_name',
        'partner_phone',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'contract_start_date',
        'contract_end_date',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(ProfitSharingScheme::class, 'scheme_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(ProfitSharingSettlement::class, 'partnership_id');
    }

    public function getTargetDisplayNameAttribute(): string
    {
        if ($this->partner_type === 'area' && $this->area) {
            return "Wilayah: {$this->area->name}";
        }
        if ($this->partner_type === 'user' && $this->user) {
            return "Mitra: {$this->user->name}";
        }
        return $this->partner_name;
    }
}

