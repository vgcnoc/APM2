<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Area;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        // 1. Sanitasi input amount (menghapus titik ribuan, koma, spasi, simbol Rp)
        if ($request->has('amount')) {
            $rawAmount = preg_replace('/[^0-9]/', '', (string)$request->amount);
            $request->merge(['amount' => $rawAmount !== '' ? (int)$rawAmount : null]);
        }

        // 2. Normalisasi expense_category_id jika kosong / string 'null'
        if ($request->has('expense_category_id')) {
            $catId = $request->expense_category_id;
            if (empty($catId) || $catId === 'null' || $catId === '' || $catId === 0 || $catId === '0') {
                $request->merge(['expense_category_id' => null]);
            }
        }

        // 3. Normalisasi area_id jika kosong / string 'null'
        if ($request->has('area_id')) {
            $areaId = $request->area_id;
            if (empty($areaId) || $areaId === 'null' || $areaId === '' || $areaId === 0 || $areaId === '0') {
                $request->merge(['area_id' => null]);
            }
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'area_id' => 'nullable|exists:areas,id',
            'description' => 'required|string|max:500',
            'expense_date' => 'required|date',
        ], [
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka yang valid.',
            'amount.min' => 'Nominal minimal Rp 1.',
            'expense_category_id.exists' => 'Kategori pengeluaran tidak ditemukan atau tidak valid.',
            'area_id.exists' => 'Area / Wilayah yang dipilih tidak valid.',
            'description.required' => 'Keterangan atau tujuan pengeluaran wajib diisi.',
            'description.max' => 'Keterangan maksimal 500 karakter.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'expense_date.date' => 'Format tanggal pengeluaran tidak valid.',
        ]);

        $areaName = null;
        if (!empty($validated['area_id'])) {
            $areaName = Area::where('id', $validated['area_id'])->value('name');
        }

        Expense::create([
            'amount' => (int) $validated['amount'],
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'area_id' => $validated['area_id'] ?? null,
            'area' => $areaName,
            'description' => trim($validated['description']),
            'expense_date' => $validated['expense_date'],
            'user_id' => auth()->id(),
        ]);

        $areaText = $areaName ? " (Wilayah {$areaName})" : "";
        return back()->with('success', 'Pengeluaran sebesar Rp ' . number_format($validated['amount'], 0, ',', '.') . $areaText . ' berhasil dicatat.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->materialTransaction || str_contains($expense->description, 'Order Toko [') || str_contains($expense->description, '[PO-')) {
            return back()->with('error', 'Pengeluaran Order Toko terikat dengan inventori material. Harap hapus atau ubah melalui menu Order Toko.');
        }

        $amountFormatted = number_format($expense->amount, 0, ',', '.');
        $desc = $expense->description;
        $expense->delete();

        return back()->with('success', "Catatan pengeluaran '{$desc}' (Rp {$amountFormatted}) berhasil dihapus.");
    }
}
