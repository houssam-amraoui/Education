<?php

namespace App\Support;

class Locale
{
    public static function isSupported(mixed $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, config('app.supported_locales'));
    }

    public static function resolve(mixed $locale): string
    {
        return self::isSupported($locale) ? $locale : self::fallback();
    }

    public static function fallback(): string
    {
        $fallback = config('app.fallback_locale');

        return self::isSupported($fallback) ? $fallback : 'en';
    }
}
