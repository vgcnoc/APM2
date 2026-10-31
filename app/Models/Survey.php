<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'area_id', 'odp_id', 'port_number', 'surveyor_id', 'signal_loss',
        'distance_meters', 'port_available', 'feasibility',
        'survey_date', 'notes', 'photos',
        'rolled_back_at', 'rolled_back_by',
    ];

    protected function casts(): array
    {
        return [
            'signal_loss' => 'decimal:2',
            'distance_meters' => 'decimal:2',
            'port_available' => 'boolean',
            'survey_date' => 'date',
            'rolled_back_at' => 'datetime',
            'photos' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surveyor_id');
    }
}
