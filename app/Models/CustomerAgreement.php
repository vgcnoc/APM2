<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CustomerAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_number',
        'customer_id',
        'terms_template_id',
        'stage',
        'title',
        'content_snapshot',
        'customer_name',
        'customer_phone',
        'customer_signature',
        'customer_signed_at',
        'employee_id',
        'employee_name',
        'employee_signature',
        'employee_signed_at',
        'ip_address',
        'user_agent',
        'status',
        'notes',
    ];

    protected $casts = [
        'customer_signed_at' => 'datetime',
        'employee_signed_at' => 'datetime',
    ];

    /**
     * Relasi ke Pelanggan
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi ke Template S&K
     */
    public function template()
    {
        return $this->belongsTo(SubscriptionTermsTemplate::class, 'terms_template_id');
    }

    /**
     * Relasi ke Karyawan / Petugas
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Generate Nomor Perjanjian Unik
     */
    public static function generateAgreementNumber(string $stage = 'booking'): string
    {
        $prefix = match ($stage) {
            'survey' => 'SK-SRV',
            'installation' => 'SK-INS',
            'booking' => 'SK-BKG',
            default => 'SK-AGR',
        };

        $yearMonth = Carbon::now()->format('Ym');
        $datePrefix = "{$prefix}-{$yearMonth}-";

        $lastAgreement = self::where('agreement_number', 'LIKE', "{$datePrefix}%")
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastAgreement && preg_match('/-(\d{4})$/', $lastAgreement->agreement_number, $matches)) {
            $sequence = intval($matches[1]) + 1;
        }

        return $datePrefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}

