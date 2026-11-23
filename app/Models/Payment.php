<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, \App\Traits\BelongsToCompany;

    protected $fillable = [
        'company_id',
        'invoice_id', 'customer_id', 'amount', 'payment_method', 'payment_channel',
        'payment_date', 'reference_number', 'notes', 'proof_image',
        'status', 'collected_by'
    ];

    protected $appends = ['proof_url'];

    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_image ? asset('storage/' . $this->proof_image) : null;
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function syncInvoiceStatus(): void
    {
        if (!$this->invoice_id) {
            return;
        }

        $invoice = Invoice::with(['payments', 'customer'])->find($this->invoice_id);
        if (!$invoice) {
            return;
        }

        $totalPaid = (int) round($invoice->payments()->where('status', 'verified')->sum('amount'));

        $newStatus = 'unpaid';
        if ($invoice->amount > 0 && $totalPaid >= $invoice->amount) {
            $newStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $newStatus = 'partial';
        }

        if ($invoice->status !== $newStatus) {
            $invoice->status = $newStatus;
            $invoice->saveQuietly();
        }

        if ($newStatus === 'paid' && !$invoice->is_reseller_balance && $invoice->customer) {
            app(\App\Services\BillingIsolationService::class)->releaseAfterPayment($invoice->customer);
        }
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->company_id)) {
                if (!empty($payment->invoice_id)) {
                    $inv = Invoice::withoutGlobalScopes()->find($payment->invoice_id);
                    if ($inv && $inv->company_id) {
                        $payment->company_id = (int) $inv->company_id;
                    }
                } elseif (!empty($payment->customer_id)) {
                    $cust = Customer::withoutGlobalScopes()->find($payment->customer_id);
                    if ($cust && $cust->company_id) {
                        $payment->company_id = (int) $cust->company_id;
                    }
                }
            }
        });

        static::saved(function (Payment $payment) {
            $payment->syncInvoiceStatus();
        });

        static::deleted(function (Payment $payment) {
            $payment->syncInvoiceStatus();

            // Bersihkan file bukti pembayaran jika ada
            if ($payment->proof_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($payment->proof_image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($payment->proof_image);
            }
        });
    }
}
