<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'analytics.settings';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        // Try to parse as numeric
        if (is_numeric($setting->value)) {
            return (float) $setting->value;
        }

        return $setting->value;
    }

    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, mixed $value, ?string $description = null): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'description' => $description,
            ]
        );
    }
}
