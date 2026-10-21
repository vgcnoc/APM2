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

        // Jika invoice lunas dan pelanggan sedang isolir/suspended, cek apakah semua tunggakan jatuh tempo sudah lunas
        if ($newStatus === 'paid' && $invoice->customer) {
            $customer = $invoice->customer;
            if (in_array($customer->status, ['isolir', 'suspended'])) {
                $hasOtherUnpaid = Invoice::where('customer_id', $customer->id)
                    ->where('id', '!=', $invoice->id)
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->where('due_date', '<=', now())
                    ->exists();

                if (!$hasOtherUnpaid) {
                    $customer->update(['status' => 'active', 'suspend_start' => null, 'suspend_end' => null]);
                    try {
                        $radius = app(\App\Services\RadiusService::class);
                        if ($radius && $radius->enabled()) {
                            $radius->guard(function($r) use ($customer) {
                                $r->syncCustomer($customer);
                                $accounts = $r->customerAccounts($customer);
                                foreach (array_keys($accounts) as $u) {
                                    $r->disconnect($u);
                                }
                            });
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('[Payment::syncInvoiceStatus] Radius auto-open isolir error: ' . $e->getMessage());
                    }
                }
            }
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
