<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number', 'customer_id', 'category', 'priority',
        'subject', 'description', 'status', 'assigned_to',
        'resolved_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TechnicianSchedule::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_number)) {
                $prefix = 'TKT-' . date('Ymd') . '-';
                $lastNum = static::where('ticket_number', 'like', $prefix . '%')
                    ->orderByDesc('id')->value('ticket_number');
                $next = $lastNum ? (int) substr($lastNum, -4) + 1 : 1;
                $ticket->ticket_number = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
