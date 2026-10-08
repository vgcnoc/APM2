<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'odp_id', 'port_number', 'surveyor_id', 'signal_loss',
        'distance_meters', 'port_available', 'feasibility',
        'survey_date', 'notes', 'photos',
    ];

    protected function casts(): array
    {
        return [
            'signal_loss' => 'decimal:2',
            'distance_meters' => 'decimal:2',
            'port_available' => 'boolean',
            'survey_date' => 'date',
            'photos' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
