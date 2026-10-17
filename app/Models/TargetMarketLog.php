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
        'base_reward' => 'integer',
        'tier_reward' => 'integer',
        'area_reward' => 'integer',
        'package_reward' => 'integer',
        'target_achieved_reward' => 'integer',
        'total_reward' => 'integer',
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
