<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Deduction extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'deduction_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Deduction $deduction) {
            if (empty($deduction->company_id) && !empty($deduction->user_id)) {
                $user = User::withoutGlobalScopes()->find($deduction->user_id);
                if ($user && $user->company_id) {
                    $deduction->company_id = (int) $user->company_id;
                }
            }
        });
    }
}
