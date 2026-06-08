<?php

namespace Modules\Queue\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            return static::query()->where('key', $key)->value('value') ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }

    public static function setValue(string $key, mixed $value): void
    {
        try {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_scalar($value) || $value === null ? $value : json_encode($value)]
            );
        } catch (Throwable) {
        }
    }
}

