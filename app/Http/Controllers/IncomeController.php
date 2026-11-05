<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\Area;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    /**
     * Simpan catatan pemasukan kas baru (non-tagihan / manual income).
     */
    public function store(Request $request)
    {
        // 1. Sanitasi input amount (menghapus titik ribuan, koma, spasi, simbol Rp)
        if ($request->has('amount')) {
            $rawAmount = preg_replace('/[^0-9]/', '', (string)$request->amount);
            $request->merge(['amount' => $rawAmount !== '' ? (int)$rawAmount : null]);
        }

        // 2. Normalisasi income_category_id jika kosong / string 'null'
        if ($request->has('income_category_id')) {
            $catId = $request->income_category_id;
            if (empty($catId) || $catId === 'null' || $catId === '' || $catId === 0 || $catId === '0') {
                $request->merge(['income_category_id' => null]);
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
            'income_category_id' => 'nullable|exists:income_categories,id',
            'area_id' => 'nullable|exists:areas,id',
            'source' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'income_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
        ], [
            'amount.required' => 'Nominal pemasukan wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka yang valid.',
            'amount.min' => 'Nominal minimal Rp 1.',
            'income_category_id.exists' => 'Kategori pemasukan tidak ditemukan atau tidak valid.',
            'area_id.exists' => 'Area / Wilayah yang dipilih tidak valid.',
            'description.required' => 'Keterangan atau rincian sumber pemasukan wajib diisi.',
            'description.max' => 'Keterangan maksimal 500 karakter.',
            'income_date.required' => 'Tanggal pemasukan wajib diisi.',
            'income_date.date' => 'Format tanggal pemasukan tidak valid.',
        ]);

        $areaName = null;
        if (!empty($validated['area_id'])) {
            $areaName = Area::where('id', $validated['area_id'])->value('name');
        }

        $income = Income::create([
            'amount' => (int) $validated['amount'],
            'income_category_id' => $validated['income_category_id'] ?? null,
            'area_id' => $validated['area_id'] ?? null,
            'area' => $areaName,
            'source' => !empty($validated['source']) ? trim($validated['source']) : null,
            'description' => trim($validated['description']),
            'income_date' => $validated['income_date'],
            'payment_method' => $validated['payment_method'] ?? 'transfer',
            'reference_no' => !empty($validated['reference_no']) ? trim($validated['reference_no']) : null,
            'user_id' => auth()->id(),
        ]);

        $areaText = $areaName ? " (Wilayah {$areaName})" : "";
        $sourceText = !empty($validated['source']) ? " dari " . trim($validated['source']) : "";
        return back()->with('success', 'Pemasukan sebesar Rp ' . number_format($validated['amount'], 0, ',', '.') . $sourceText . $areaText . ' berhasil dicatat ke buku kas.');
    }

    /**
     * Hapus catatan pemasukan manual.
     */
    public function destroy(Income $income)
    {
        $amountFormatted = number_format($income->amount, 0, ',', '.');
        $desc = $income->description;
        $income->delete();

        return back()->with('success', "Catatan pemasukan '{$desc}' (Rp {$amountFormatted}) berhasil dihapus dari buku kas.");
    }
}

