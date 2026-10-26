<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
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
