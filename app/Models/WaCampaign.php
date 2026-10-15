<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaCampaign extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'target_area_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WaLog::class, 'wa_campaign_id');
    }
}
