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
        return static::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => (string)$value,
                'label' => $label,
                'description' => $description,
            ], fn($v) => !is_null($v))
        );
    }
}
