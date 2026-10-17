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
}
