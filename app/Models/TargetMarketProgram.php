<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TargetMarketProgram extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'period_month' => 'integer',
        'period_year' => 'integer',
        'target_quota' => 'integer',
        'target_area_id' => 'integer',
        'reward_flat' => 'integer',
        'reward_bonus_target' => 'integer',
        'rules' => 'array',
        'is_active' => 'boolean',
        'auto_disburse' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'target_area_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TargetMarketLog::class, 'program_id');
    }

    public function getConceptLabelAttribute(): string
    {
        return match ($this->concept) {
            'milestone_volume' => 'Tier / Milestone Volume',
            'area_expansion' => 'Penetrasi Area / ODP Baru',
            'package_tier' => 'Nilai Paket / Revenue',
            'hybrid' => 'Hybrid Smart Target',
            default => ucfirst(str_replace('_', ' ', $this->concept)),
        };
    }
}
