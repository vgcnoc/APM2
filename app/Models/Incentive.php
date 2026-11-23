<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Incentive extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'incentive_date' => 'date',
        'disbursed_at' => 'date',
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
        static::creating(function (Incentive $incentive) {
            if (empty($incentive->company_id) && !empty($incentive->user_id)) {
                $user = User::withoutGlobalScopes()->find($incentive->user_id);
                if ($user && $user->company_id) {
                    $incentive->company_id = (int) $user->company_id;
                }
            }
        });
    }
}
