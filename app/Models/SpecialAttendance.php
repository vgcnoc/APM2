<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class SpecialAttendance extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'is_synced_to_payroll' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->company_id) && !empty($model->employee_id)) {
                $empCompany = Employee::withoutGlobalScopes()->where('id', $model->employee_id)->value('company_id');
                if ($empCompany) {
                    $model->company_id = (int) $empCompany;
                }
            }
        });
    }

    public function team()
    {
        return $this->belongsTo(SpecialTeam::class, 'special_team_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
