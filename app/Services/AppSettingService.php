<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class AppSettingService
{
    public const CACHE_TTL = 86400;

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("app_setting:{$key}", self::CACHE_TTL, function () use ($key, $default) {
            return AppSetting::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public function set(string $key, mixed $value): void
    {
        AppSetting::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );

        Cache::forget("app_setting:{$key}");
    }
}
