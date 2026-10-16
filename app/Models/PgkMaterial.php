<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PgkMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'pgk_ticket_id',
        'material_id',
        'material_transaction_item_id',
        'trx_number',
        'quantity_prepared',
        'quantity_used',
        'quantity_returned',
        'unit',
        'notes',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PgkTicket::class, 'pgk_ticket_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function materialTransactionItem(): BelongsTo
    {
        return $this->belongsTo(MaterialTransactionItem::class, 'material_transaction_item_id');
    }
}
