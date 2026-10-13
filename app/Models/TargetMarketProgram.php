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
        'period_month' => 'integer',
        'period_year' => 'integer',
        'target_quota' => 'integer',
        'reward_flat' => 'decimal:2',
        'reward_bonus_target' => 'decimal:2',
        'rules' => 'array',
        'is_active' => 'boolean',
        'auto_disburse' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
