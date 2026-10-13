<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialTeam extends Model
{
    protected $guarded = ['id'];

    public function members()
    {
        return $this->hasMany(SpecialTeamMember::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'special_team_members')
            ->withPivot('role', 'is_leader')
            ->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(SpecialAttendance::class);
    }
}
