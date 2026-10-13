<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PgjMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'pgj_ticket_id',
        'material_id',
        'quantity_prepared',
        'quantity_used',
        'quantity_returned',
        'unit',
    ];

    protected $casts = [
        'quantity_prepared' => 'decimal:2',
        'quantity_used' => 'decimal:2',
        'quantity_returned' => 'decimal:2',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PgjTicket::class, 'pgj_ticket_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
