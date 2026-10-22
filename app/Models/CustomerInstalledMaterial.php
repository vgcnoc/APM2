<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerInstalledMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'material_id',
        'item_name',
        'quantity',
        'unit',
        'serial_number',
        'installed_at',
        'installation_source',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'installed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function dismantles(): HasMany
    {
        return $this->hasMany(CustomerMaterialDismantle::class, 'customer_installed_material_id');
    }
}
