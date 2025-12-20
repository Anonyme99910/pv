<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get a setting value with caching
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value and clear cache
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null): void
    {
        $storedValue = $type === 'encrypted' ? Crypt::encryptString($value) : $value;

        if ($type === 'json' && is_array($value)) {
            $storedValue = json_encode($value);
        }

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        Cache::forget("setting.{$key}");
    }

    /**
     * Get all settings for a group
     */
    public static function getGroup(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            $settings = static::where('group', $group)->get();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = static::castValue($setting->value, $setting->type);
            }

            return $result;
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("setting.{$setting->key}");
        }

        $groups = static::distinct('group')->pluck('group');
        foreach ($groups as $group) {
            Cache::forget("settings.group.{$group}");
        }
    }

    /**
     * Cast value based on type
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            'encrypted' => Crypt::decryptString($value),
            default => $value,
        };
    }
}
