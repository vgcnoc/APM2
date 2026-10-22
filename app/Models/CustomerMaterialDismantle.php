<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMaterialDismantle extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_type',
        'ticket_id',
        'ticket_number',
        'customer_id',
        'customer_installed_material_id',
        'material_id',
        'item_name',
        'replacement_type',
        'quantity',
        'unit',
        'condition',
        'damage_reason',
        'technician_id',
        'technician_name',
        'handover_status',
        'received_by',
        'received_at',
        'received_notes',
        'disposition',
        'material_transaction_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installedMaterial(): BelongsTo
    {
        return $this->belongsTo(CustomerInstalledMaterial::class, 'customer_installed_material_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function materialTransaction(): BelongsTo
    {
        return $this->belongsTo(MaterialTransaction::class);
    }
}
