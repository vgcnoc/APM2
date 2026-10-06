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
        'amount', 'due_date', 'issued_date', 'status', 'is_prorata', 'promise_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'issued_date' => 'date',
            'promise_date' => 'date',
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

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getRemainingAttribute(): float
    {
        return max(0, $this->amount - $this->total_paid);
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
    }
}
