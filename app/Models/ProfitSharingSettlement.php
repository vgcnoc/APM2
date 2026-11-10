<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitSharingSettlement extends Model
{
    use HasFactory;

    protected $table = 'profit_sharing_settlements';

    protected $fillable = [
        'settlement_number',
        'partnership_id',
        'period_month',
        'period_year',
        'total_customers',
        'total_paid_invoices',
        'total_gross_revenue',
        'total_tax_deductions',
        'total_operational_deductions',
        'net_revenue',
        'scheme_snapshot',
        'partner_share_amount',
        'isp_share_amount',
        'status',
        'paid_at',
        'paid_by',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'total_customers' => 'integer',
        'total_paid_invoices' => 'integer',
        'total_gross_revenue' => 'decimal:2',
        'total_tax_deductions' => 'decimal:2',
        'total_operational_deductions' => 'decimal:2',
        'net_revenue' => 'decimal:2',
        'scheme_snapshot' => 'array',
        'partner_share_amount' => 'decimal:2',
        'isp_share_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(ProfitSharingPartnership::class, 'partnership_id');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $m = $months[$this->period_month] ?? "Bulan {$this->period_month}";
        return "{$m} {$this->period_year}";
    }
}

