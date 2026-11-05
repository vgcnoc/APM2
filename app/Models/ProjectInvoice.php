<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'client_name',
        'client_pic',
        'client_phone',
        'client_email',
        'client_address',
        'client_npwp',
        'po_spk_number',
        'project_title',
        'project_location',
        'issue_date',
        'due_date',
        'payment_scheme',
        'subtotal',
        'discount',
        'tax_ppn_percent',
        'tax_ppn_amount',
        'tax_pph23_percent',
        'tax_pph23_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'payment_method',
        'payment_bank_info',
        'paid_at',
        'payment_notes',
        'notes',
        'income_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'float',
        'discount' => 'float',
        'tax_ppn_percent' => 'float',
        'tax_ppn_amount' => 'float',
        'tax_pph23_percent' => 'float',
        'tax_pph23_amount' => 'float',
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'remaining_amount' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(ProjectInvoiceItem::class, 'project_invoice_id')->orderBy('sort_order');
    }

    public function income()
    {
        return $this->belongsTo(Income::class, 'income_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Generate Nomor Invoice Proyek Otomatis: INV-PRJ-YYYYMM-XXXX
     */
    public static function generateNextInvoiceNumber(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $prefix = 'INV-PRJ-' . $date->format('Ym') . '-';

        $lastNumber = self::where('invoice_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->value('invoice_number');

        if ($lastNumber) {
            $lastSeq = (int) substr($lastNumber, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung ulang seluruh subtotal, pajak, diskon, dan status pembayaran
     */
    public function recalculateTotals(): self
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $totalItem = round(((float)$item->qty) * ((float)$item->unit_price), 2);
            $item->total_price = $totalItem;
            $item->save();
            $subtotal += $totalItem;
        }

        $this->subtotal = $subtotal;
        $discount = (float)($this->discount ?? 0);
        $afterDiscount = max(0, $subtotal - $discount);

        // PPN
        $ppnPercent = (float)($this->tax_ppn_percent ?? 0);
        $ppnAmount = $ppnPercent > 0 ? round(($afterDiscount * $ppnPercent) / 100, 2) : 0;
        $this->tax_ppn_amount = $ppnAmount;

        // PPh 23 (Pemotongan Jasa)
        $pphPercent = (float)($this->tax_pph23_percent ?? 0);
        $pphAmount = $pphPercent > 0 ? round(($afterDiscount * $pphPercent) / 100, 2) : 0;
        $this->tax_pph23_amount = $pphAmount;

        // Grand Total: (Subtotal - Diskon + PPN) - PPh 23
        $grandTotal = max(0, $afterDiscount + $ppnAmount - $pphAmount);
        $this->total_amount = $grandTotal;

        $paid = (float)($this->paid_amount ?? 0);
        $this->remaining_amount = max(0, $grandTotal - $paid);

        if ($this->status !== 'cancelled' && $this->status !== 'draft') {
            if ($paid >= $grandTotal && $grandTotal > 0) {
                $this->status = 'paid';
            } elseif ($paid > 0 && $paid < $grandTotal) {
                $this->status = 'partial';
            } else {
                $this->status = 'unpaid';
            }
        }

        $this->save();

        return $this;
    }

    /**
     * Sinkronisasi mutasi ke Buku Kas Umum (Incomes)
     */
    public function syncWithKasIncome(?int $userId = null): ?Income
    {
        $paid = (float)($this->paid_amount ?? 0);

        // Jika invoice dibatalkan / draft / belum ada bayar, hapus income terkait jika ada
        if ($this->status === 'cancelled' || $this->status === 'draft' || $paid <= 0) {
            if ($this->income_id) {
                $oldIncome = Income::find($this->income_id);
                if ($oldIncome) {
                    $oldIncome->delete();
                }
                $this->income_id = null;
                $this->saveQuietly();
            }
            return null;
        }

        // Cari atau buat kategori Pemasukan Proyek JSA / PRJ
        $category = IncomeCategory::where('code', 'PRJ')->first()
            ?? IncomeCategory::where('code', 'JSA')->first()
            ?? IncomeCategory::firstOrCreate(
                ['code' => 'PRJ'],
                [
                    'name' => 'Invoice Proyek & Jasa Kerjasama',
                    'description' => 'Pemasukan dari invoice proyek B2B, penarikan kabel, integrasi instalasi eksternal',
                    'color' => 'indigo',
                    'is_active' => true,
                ]
            );

        $description = 'Pembayaran Invoice Proyek ' . $this->invoice_number . ' - ' . $this->client_name . ' (' . $this->project_title . ')';
        if ($this->status === 'partial') {
            $description .= ' [Termin / DP]';
        } else {
            $description .= ' [Lunas]';
        }

        $payDate = $this->paid_at ? Carbon::parse($this->paid_at)->toDateString() : Carbon::now()->toDateString();
        $payMethod = $this->payment_method ?: 'transfer';

        $incomeData = [
            'amount' => (int) round($paid),
            'income_category_id' => $category?->id,
            'source' => $this->client_name,
            'description' => $description,
            'income_date' => $payDate,
            'payment_method' => $payMethod,
            'reference_no' => $this->invoice_number,
            'user_id' => $userId ?? $this->created_by ?? auth()->id(),
        ];

        if ($this->income_id) {
            $income = Income::find($this->income_id);
            if ($income) {
                $income->update($incomeData);
                return $income;
            }
        }

        $income = Income::create($incomeData);
        $this->income_id = $income->id;
        $this->saveQuietly();

        return $income;
    }
}

