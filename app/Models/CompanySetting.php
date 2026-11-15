<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Helper statis untuk mengambil nilai setting per company
     */
    public static function getValue(int $companyId, string $key, $default = null)
    {
        $cacheKey = "company_setting_{$companyId}_{$key}";
        return Cache::remember($cacheKey, 3600, function () use ($companyId, $key, $default) {
            $record = self::where('company_id', $companyId)->where('key', $key)->first();
            if ($record && $record->value !== null) {
                return $record->value;
            }
            return Setting::get($key, $default);
        });
    }

    /**
     * Helper statis untuk menyimpan setting per company
     */
    public static function setValue(int $companyId, string $key, $value): void
    {
        self::updateOrInsert(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'updated_at' => now()]
        );
        Cache::forget("company_setting_{$companyId}_{$key}");
    }
}

