<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialAttendance extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'is_synced_to_payroll' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(SpecialTeam::class, 'special_team_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
