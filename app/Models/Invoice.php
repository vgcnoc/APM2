<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'invoice_number', 'period_month', 'period_year',
        'amount', 'due_date', 'issued_date', 'status', 'is_prorata', 'billing_type', 'billing_cycle', 'promise_date',
        'is_reseller_balance', 'notes',
        'payment_ref', 'payment_url', 'payment_channel', 'payment_gateway_payload'
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_date' => 'date',
            'issued_date' => 'date',
            'promise_date' => 'date',
            'isolation_at' => 'datetime',
            'is_prorata' => 'boolean',
            'is_reseller_balance' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return ($months[$this->period_month] ?? '') . ' ' . $this->period_year;
    }

    public function getTotalPaidAttribute(): int
    {
        if ($this->relationLoaded('payments')) {
            return (int) round($this->payments->where('status', 'verified')->sum('amount'));
        }
        return (int) round($this->payments()->where('status', 'verified')->sum('amount'));
    }

    public function getRemainingAttribute(): int
    {
        return (int) max(0, round($this->amount - $this->total_paid));
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $prefix = 'INV-' . date('Ym') . '-';
                $lastNum = static::where('invoice_number', 'like', $prefix . '%')
                    ->orderByDesc('id')->value('invoice_number');
                $next = $lastNum ? (int) substr($lastNum, -4) + 1 : 1;
                $invoice->invoice_number = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function (Invoice $invoice) {
            if (!$invoice->is_reseller_balance && $invoice->issued_date
                && (!$invoice->exists || $invoice->isDirty('issued_date') || !$invoice->isolation_at)) {
                $invoice->isolation_at = app(\App\Services\BillingIsolationSchedule::class)->forIssueDate($invoice->issued_date);
            }
            if ($invoice->exists && $invoice->isDirty('amount') && $invoice->amount > 0) {
                $totalPaid = (int) round($invoice->payments()->where('status', 'verified')->sum('amount'));
                if ($totalPaid >= $invoice->amount) {
                    $invoice->status = 'paid';
                } elseif ($totalPaid > 0) {
                    $invoice->status = 'partial';
                } else {
                    $invoice->status = 'unpaid';
                }
            }
        });

        static::updated(function (Invoice $invoice) {
            if (\App\Models\Customer::$isMigrating) {
                return;
            }
            if ($invoice->wasChanged('status') && $invoice->status === 'paid' && !$invoice->is_reseller_balance) {
                try {
                    \App\Services\AffiliateService::recordMonthlyCommission($invoice);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Affiliate] Monthly commission error: ' . $e->getMessage());
                }
            }
        });

        static::deleting(function (Invoice $invoice) {
            try {
                foreach ($invoice->payments as $payment) {
                    if ($payment->proof_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($payment->proof_image)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($payment->proof_image);
                    }
                    $payment->delete();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Invoice::deleting] Error deleting payments: ' . $e->getMessage());
            }
        });
    }
}
