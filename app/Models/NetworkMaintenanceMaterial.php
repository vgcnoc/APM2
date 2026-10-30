<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkMaintenanceMaterial extends Model
{
    use HasFactory;

    protected $table = 'network_maintenance_materials';

    protected $fillable = [
        'network_maintenance_id',
        'material_id',
        'material_transaction_item_id',
        'trx_number',
        'quantity_prepared',
        'quantity_used',
        'quantity_returned',
        'unit',
        'notes',
    ];

    protected $casts = [
        'quantity_prepared' => 'decimal:2',
        'quantity_used' => 'decimal:2',
        'quantity_returned' => 'decimal:2',
    ];

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(NetworkMaintenance::class, 'network_maintenance_id');
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

