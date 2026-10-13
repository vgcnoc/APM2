<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldDuty extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'is_allowance_synced' => 'boolean',
        'is_regular_attendance_synced' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
