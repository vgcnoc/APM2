<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetMarketLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'base_reward' => 'decimal:2',
        'tier_reward' => 'decimal:2',
        'area_reward' => 'decimal:2',
        'package_reward' => 'decimal:2',
        'target_achieved_reward' => 'decimal:2',
        'total_reward' => 'decimal:2',
        'concept_breakdown' => 'array',
        'period_month' => 'integer',
        'period_year' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TargetMarketProgram::class, 'program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function incentive(): BelongsTo
    {
        return $this->belongsTo(Incentive::class);
    }
}
