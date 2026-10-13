<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PgjTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'segment_type',
        'area_id',
        'odc_id',
        'odp_id',
        'affected_customers_count',
        'location_name',
        'latitude',
        'longitude',
        'otdr_distance',
        'source_report',
        'category',
        'priority',
        'status',
        'reported_issue',
        'admin_notes',
        'escalation_reason',
        'scheduled_date',
        'scheduled_time',
        'lead_notes',
        'action_taken',
        'core_details',
        'optical_loss_before',
        'optical_loss_after',
        'start_time',
        'end_time',
        'photo_before',
        'photo_after',
        'resolution_notes',
        'created_by',
        'lead_technician_id',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'resolved_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'affected_customers_count' => 'integer',
    ];

    /**
     * Boot model untuk auto-generate No. Tiket PGJ
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $datePrefix = 'PGJ-' . date('Ymd') . '-';
                $lastTicket = self::where('ticket_number', 'like', $datePrefix . '%')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastTicket) {
                    $lastNumber = (int) substr($lastTicket->ticket_number, -4);
                    $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                } else {
                    $newNumber = '0001';
                }

                $ticket->ticket_number = $datePrefix . $newNumber;
            }
        });
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function odc(): BelongsTo
    {
        return $this->belongsTo(Odc::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leadTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_technician_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pgj_ticket_user', 'pgj_ticket_id', 'user_id')
            ->withTimestamps();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(PgjMaterial::class, 'pgj_ticket_id');
    }
}
