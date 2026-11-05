<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'customer_id',
        'income_category_id',
        'area_id',
        'area',
        'source',
        'description',
        'income_date',
        'payment_method',
        'reference_no',
        'attachment',
        'user_id',
    ];

    protected $casts = [
        'income_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Income $income) {
            if ($income->isDirty('area_id') && !empty($income->area_id)) {
                $areaName = Area::where('id', $income->area_id)->value('name');
                if ($areaName) {
                    $income->area = $areaName;
                }
            } elseif ($income->isDirty('area') && !empty($income->area)) {
                $areaId = Area::where('name', trim($income->area))->value('id');
                if ($areaId) {
                    $income->area_id = $areaId;
                }
            } elseif (empty($income->area_id) && !empty($income->area)) {
                $areaId = Area::where('name', trim($income->area))->value('id');
                if ($areaId) {
                    $income->area_id = $areaId;
                }
            } elseif (!empty($income->area_id) && empty($income->area)) {
                $areaName = Area::where('id', $income->area_id)->value('name');
                if ($areaName) {
                    $income->area = $areaName;
                }
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function areaModel()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function projectInvoice()
    {
        return $this->hasOne(ProjectInvoice::class, 'income_id');
    }
}

