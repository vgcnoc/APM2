<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'description' => 'required|string|max:255',
            'expense_date' => 'required|date',
        ]);

        Expense::create([
            'amount' => $validated['amount'],
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'description' => $validated['description'],
            'expense_date' => $validated['expense_date'],
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }
}
