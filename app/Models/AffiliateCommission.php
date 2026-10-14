<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'customer_id',
        'invoice_id',
        'source_user_id',
        'type',
        'tier_level',
        'amount',
        'description',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tier_level' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }
}
