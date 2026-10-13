<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialTeamMember extends Model
{
    protected $guarded = ['id'];

    public function team()
    {
        return $this->belongsTo(SpecialTeam::class, 'special_team_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
