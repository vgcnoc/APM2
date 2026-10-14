<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'referral_code',
        'upline_id',
        'whatsapp',
        'address',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'balance',
        'total_earned',
        'total_withdrawn',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function upline(): BelongsTo
    {
        return $this->belongsTo(User::class, 'upline_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'affiliate_id', 'user_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(AffiliateWithdrawal::class, 'affiliate_id', 'user_id');
    }

    /**
     * Downline Level 1 (langsung di bawah affiliate ini)
     */
    public function downlinesLevel1(): HasMany
    {
        return $this->hasMany(AffiliateProfile::class, 'upline_id', 'user_id');
    }

    /**
     * Pelanggan yang didaftarkan lewat affiliate ini
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'sales_id', 'user_id');
    }
}
