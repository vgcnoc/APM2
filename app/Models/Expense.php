<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'expense_category_id',
        'area_id',
        'area',
        'description',
        'expense_date',
        'user_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Expense $expense) {
            if ($expense->isDirty('area_id') && !empty($expense->area_id)) {
                $areaName = Area::where('id', $expense->area_id)->value('name');
                if ($areaName) {
                    $expense->area = $areaName;
                }
            } elseif ($expense->isDirty('area') && !empty($expense->area)) {
                $areaId = Area::where('name', trim($expense->area))->value('id');
                if ($areaId) {
                    $expense->area_id = $areaId;
                }
            } elseif (empty($expense->area_id) && !empty($expense->area)) {
                $areaId = Area::where('name', trim($expense->area))->value('id');
                if ($areaId) {
                    $expense->area_id = $areaId;
                }
            } elseif (!empty($expense->area_id) && empty($expense->area)) {
                $areaName = Area::where('id', $expense->area_id)->value('name');
                if ($areaName) {
                    $expense->area = $areaName;
                }
            }
        });
    }

    public function areaModel()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function areaRef()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function materialTransaction()
    {
        return $this->hasOne(MaterialTransaction::class, 'expense_id');
    }

    /**
     * Scope untuk mengecualikan pengeluaran Order Toko / Pembelian Material
     */
    public function scopeWithoutOrderToko($query)
    {
        return $query->whereDoesntHave('materialTransaction')
            ->where(function ($q) {
                $q->whereNull('expense_category_id')
                  ->orWhereHas('category', function ($cat) {
                      $cat->where('code', '!=', 'MAT');
                  });
            })
            ->where('description', 'not like', '%Order Toko%');
    }
}
