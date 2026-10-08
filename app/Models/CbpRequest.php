<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CbpRequest extends Model
{
    protected $fillable = [
        'cbp_number', 'customer_id', 'reason', 'status',
        'assigned_to', 'created_by', 'completed_at', 'notes',
        'start_time', 'end_time', 'photo'
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function technicians()
    {
        return $this->belongsToMany(User::class, 'cbp_request_user', 'cbp_request_id', 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'entity_id')->where('entity_type', self::class)->orderBy('created_at', 'desc');
    }

    protected static function booted(): void
    {
        static::creating(function (CbpRequest $cbp) {
            if (empty($cbp->cbp_number)) {
                $prefix = 'CBP-' . date('Ymd') . '-';
                $lastNum = static::where('cbp_number', 'like', $prefix . '%')
                    ->orderByDesc('id')->value('cbp_number');
                $next = $lastNum ? (int) substr($lastNum, -4) + 1 : 1;
                $cbp->cbp_number = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
