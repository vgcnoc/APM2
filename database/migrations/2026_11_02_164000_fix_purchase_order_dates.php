<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\MaterialTransaction;
use App\Models\Expense;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $transactions = MaterialTransaction::where('transaction_number', 'like', 'PO-%')->get();

        foreach ($transactions as $t) {
            if (preg_match('/PO-(\d{4})(\d{2})(\d{2})-/', $t->transaction_number, $matches)) {
                $expectedDate = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
                $currentRaw = $t->getRawOriginal('date');

                if ($currentRaw !== $expectedDate) {
                    $t->update(['date' => $expectedDate]);

                    if ($t->expense_id) {
                        Expense::where('id', $t->expense_id)->update(['expense_date' => $expectedDate]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};

