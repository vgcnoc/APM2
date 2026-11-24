<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;

class WorkSchedule extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'shift_id',
        'date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
