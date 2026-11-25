<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use App\Models\ExpenseCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ambil Kategori ID untuk Uang Mingguan-1 dan Uang Mingguan-2 di Company 1
        $catMng1 = ExpenseCategory::withoutGlobalScopes()
            ->where('company_id', 1)
            ->where(function ($q) {
                $q->where('code', 'MNG-1')
                  ->orWhere('name', 'like', '%Mingguan-1%')
                  ->orWhere('name', 'like', '%Mingguan 1%');
            })
            ->first();

        $catMng2 = ExpenseCategory::withoutGlobalScopes()
            ->where('company_id', 1)
            ->where(function ($q) {
                $q->where('code', 'MNG-2')
                  ->orWhere('name', 'like', '%Mingguan-2%')
                  ->orWhere('name', 'like', '%Mingguan 2%');
            })
            ->first();

        if ($catMng1) {
            Expense::withoutGlobalScopes()
                ->where('company_id', 1)
                ->where('description', 'like', 'Pencairan Insentif%')
                ->where(function ($q) {
                    $q->where('description', 'like', '% - UANG MINGGUAN 1%')
                      ->orWhere('description', 'like', '% - Uang Mingguan (%');
                })
                ->update(['expense_category_id' => $catMng1->id]);
        }

        if ($catMng2) {
            Expense::withoutGlobalScopes()
                ->where('company_id', 1)
                ->where('description', 'like', 'Pencairan Insentif%')
                ->where('description', 'like', '% - UANG MINGGUAN 2%')
                ->update(['expense_category_id' => $catMng2->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu di-revert
    }
};

