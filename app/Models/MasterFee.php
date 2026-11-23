<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class MasterFee extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'nominal' => 'integer',
    ];

    /**
     * Ambil nominal fee aktif untuk tipe tertentu.
     * Cek di company aktif terlebih dahulu, jika belum ada, fallback ke template Pusat (company_id = 1).
     */
    public static function getFeeNominal(string $type, float $default = 0): float
    {
        $val = static::where('type', $type)->where('is_active', true)->value('nominal');
        if ($val !== null) {
            return (float) $val;
        }

        // Fallback ke template Pusat (company_id = 1) jika mitra belum konfigurasi
        $holdingVal = static::withoutGlobalScopes()
            ->where('company_id', 1)
            ->where('type', $type)
            ->where('is_active', true)
            ->value('nominal');

        return $holdingVal !== null ? (float) $holdingVal : $default;
    }
}
