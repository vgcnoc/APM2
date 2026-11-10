<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitSharingScheme extends Model
{
    use HasFactory;

    protected $table = 'profit_sharing_schemes';

    protected $fillable = [
        'name',
        'code',
        'model',
        'partner_percentage',
        'isp_percentage',
        'flat_per_customer_amount',
        'registration_bonus',
        'operational_deduction',
        'deduct_tax',
        'tiered_rules',
        'package_rules',
        'description',
        'is_active',
    ];

    protected $casts = [
        'partner_percentage' => 'decimal:2',
        'isp_percentage' => 'decimal:2',
        'flat_per_customer_amount' => 'decimal:2',
        'registration_bonus' => 'decimal:2',
        'operational_deduction' => 'decimal:2',
        'deduct_tax' => 'boolean',
        'tiered_rules' => 'array',
        'package_rules' => 'array',
        'is_active' => 'boolean',
    ];

    public function partnerships(): HasMany
    {
        return $this->hasMany(ProfitSharingPartnership::class, 'scheme_id');
    }

    public static function modelLabels(): array
    {
        return [
            'gross_percentage' => 'Persentase Omset Kotor (Gross %)',
            'net_percentage' => 'Persentase Omset Bersih (Net % Setelah Pajak/Beban)',
            'flat_per_customer' => 'Nominal Flat per Pelanggan Aktif (Rp/User)',
            'tiered_volume' => 'Bagi Hasil Bertingkat (Tiered Volume Pelanggan)',
            'per_package' => 'Kustom Berdasarkan Paket Langganan',
        ];
    }

    public function getModelLabelAttribute(): string
    {
        return static::modelLabels()[$this->model] ?? ucfirst(str_replace('_', ' ', $this->model));
    }
}

