<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfrastructureWorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'wo_number',
        'type',
        'device_type',
        'target_name',
        'area_id',
        'olt_id',
        'pon_port',
        'odc_id',
        'odc_splitter_port',
        'capacity',
        'core_capacity',
        'splitter_ratio',
        'location',
        'planned_latitude',
        'planned_longitude',
        'actual_latitude',
        'actual_longitude',
        'technician_id',
        'created_by',
        'scheduled_date',
        'status',
        'start_point',
        'end_point',
        'cable_meter_start',
        'cable_meter_end',
        'cable_length_used',
        'photo_pole',
        'photo_splicing',
        'photo_opm',
        'notes',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'created_odc_id',
        'created_odp_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'approved_at' => 'datetime',
        'planned_latitude' => 'decimal:7',
        'planned_longitude' => 'decimal:7',
        'actual_latitude' => 'decimal:7',
        'actual_longitude' => 'decimal:7',
        'capacity' => 'integer',
        'core_capacity' => 'integer',
        'pon_port' => 'integer',
        'odc_splitter_port' => 'integer',
        'cable_meter_start' => 'integer',
        'cable_meter_end' => 'integer',
        'cable_length_used' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function parentOdc(): BelongsTo
    {
        return $this->belongsTo(Odc::class, 'odc_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'infrastructure_work_order_technicians', 'work_order_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdOdc(): BelongsTo
    {
        return $this->belongsTo(Odc::class, 'created_odc_id');
    }

    public function createdOdp(): BelongsTo
    {
        return $this->belongsTo(Odp::class, 'created_odp_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(InfrastructureWorkOrderMaterial::class, 'work_order_id');
    }

    public function opticalChecks(): HasMany
    {
        return $this->hasMany(InfrastructureWorkOrderOpticalCheck::class, 'work_order_id');
    }
}
