<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PgkTicket extends Model
{
    use HasFactory, \App\Traits\HasDataScope;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'area_id',
        'complainant_name',
        'complainant_phone',
        'channel',
        'category',
        'priority',
        'reported_issue',
        'admin_notes',
        'status',
        'escalated_at',
        'escalation_reason',
        'created_by',
        'lead_technician_id',
        'scheduled_date',
        'scheduled_time',
        'lead_notes',
        'action_taken',
        'optical_power_before',
        'optical_power_after',
        'start_time',
        'end_time',
        'photo_before',
        'photo_after',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'ont_replacement_status',
        'requested_ont_notes',
        'requested_ont_at',
        'requested_by',
        'prepared_ont_id',
        'prepared_ont_at',
        'prepared_by',
        'old_ont_id',
        'ont_replacement_action',
        'ont_replacement_notes',
    ];

    protected $attributes = [
        'status' => 'admin_handling',
        'channel' => 'whatsapp',
        'ont_replacement_status' => 'none',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'requested_ont_at' => 'datetime',
            'prepared_ont_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leadTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_technician_id');
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pgk_ticket_user', 'pgk_ticket_id', 'user_id')->withTimestamps();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(PgkMaterial::class, 'pgk_ticket_id');
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(PgkMaterialRequest::class, 'pgk_ticket_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function preparedOnt(): BelongsTo
    {
        return $this->belongsTo(Ont::class, 'prepared_ont_id');
    }

    public function oldOnt(): BelongsTo
    {
        return $this->belongsTo(Ont::class, 'old_ont_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function preparedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    protected static function booted(): void
    {
        static::creating(function (PgkTicket $ticket) {
            if (empty($ticket->ticket_number)) {
                $prefix = 'PGK-' . date('Ymd') . '-';
                $last = static::where('ticket_number', 'like', $prefix . '%')
                    ->orderByDesc('id')
                    ->value('ticket_number');
                $next = $last ? (int) substr($last, -4) + 1 : 1;
                $ticket->ticket_number = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
