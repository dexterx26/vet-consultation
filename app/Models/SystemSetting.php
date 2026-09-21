<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'label',
        'description',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value, $label = null, $description = null): self
    {
        $existing = static::where('key', $key)->first();
        $label = $label ?? ($existing?->label ?? ucwords(str_replace('_', ' ', $key)));

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string)$value,
                'label' => $label,
                'description' => $description ?? $existing?->description,
            ]
        );
    }
}
