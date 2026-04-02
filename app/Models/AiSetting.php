<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        $flush = fn (self $m) => Cache::forget("ai_setting:{$m->key}");
        static::saved($flush);
        static::deleted($flush);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("ai_setting:{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
