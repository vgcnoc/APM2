<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'brand_name',
        'tagline',
        'logo_url',
        'favicon_url',
        'phone',
        'email',
        'address',
        'website',
        'invoice_kop_header',
        'invoice_footer_notes',
        'status',
        'max_customers',
        'allowed_modules',
        'is_holding',
    ];

    protected $casts = [
        'allowed_modules' => 'array',
        'is_holding' => 'boolean',
        'max_customers' => 'integer',
    ];

    /**
     * Relasi ke company settings
     */
    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Dapatkan instance Perusahaan Pusat (Holding)
     */
    public static function holding(): ?self
    {
        return Cache::remember('company_holding_record', 3600, function () {
            return self::where('is_holding', true)->first() ?: self::find(1);
        });
    }

    /**
     * Jalankan eksekusi query / fungsi dalam lingkup company tertentu secara terisolasi
     */
    public static function scoped(int $companyId, callable $callback)
    {
        $previous = app()->has('forced_company_id') ? app('forced_company_id') : null;
        app()->instance('forced_company_id', $companyId);
        try {
            return $callback();
        } finally {
            if ($previous !== null) {
                app()->instance('forced_company_id', $previous);
            } else {
                app()->forgetInstance('forced_company_id');
            }
        }
    }

    /**
     * Ambil setting spesifik untuk perusahaan ini dengan fallback
     */
    public function getSetting(string $key, $default = null)
    {
        $cacheKey = "company_setting_{$this->id}_{$key}";
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $record = $this->settings()->where('key', $key)->first();
            if ($record && $record->value !== null) {
                return $record->value;
            }
            // Fallback ke global setting jika belum ada di company_settings
            return Setting::get($key, $default);
        });
    }

    /**
     * Simpan / Update setting untuk perusahaan ini
     */
    public function setSetting(string $key, $value): void
    {
        $this->settings()->updateOrInsert(
            ['company_id' => $this->id, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'updated_at' => now()]
        );
        Cache::forget("company_setting_{$this->id}_{$key}");
    }

    /**
     * Periksa apakah modul diizinkan untuk perusahaan ini
     */
    public function hasModule(string $module): bool
    {
        if ($this->is_holding) {
            return true;
        }

        if (empty($this->allowed_modules)) {
            // Default modul dasar jika belum diset khusus
            return in_array($module, ['billing', 'customers', 'tickets']);
        }

        return in_array($module, $this->allowed_modules);
    }
}
