<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /**
     * In-memory cache for settings during request lifecycle
     */
    protected static ?array $cachedSettings = null;

    /**
     * Get all settings as key-value array (cached)
     */
    public static function getAllSettings(): array
    {
        if (self::$cachedSettings !== null) {
            return self::$cachedSettings;
        }

        return self::$cachedSettings = Cache::remember('app_settings_all_dict', 3600, function () {
            try {
                return self::query()->pluck('value', 'key')->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::getAllSettings();
        if (array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        return $default;
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value)
    {
        self::$cachedSettings = null;
        Cache::forget('app_settings_all_dict');

        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Clear the cache
     */
    public static function clearCache(): void
    {
        self::$cachedSettings = null;
        Cache::forget('app_settings_all_dict');
    }
}
