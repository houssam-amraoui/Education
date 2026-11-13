<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlatformSettings
{
    public const KEYS = ['platform_name', 'default_locale', 'enabled_locales'];

    public static function rules(): array
    {
        $locales = array_keys(config('app.supported_locales'));

        return [
            'settings' => ['required', 'array:'.implode(',', self::KEYS)],
            'settings.platform_name' => ['required', 'string', 'max:255'],
            'settings.default_locale' => ['required', 'string', Rule::in($locales), 'in_array:settings.enabled_locales.*'],
            'settings.enabled_locales' => ['required', 'array', 'list', 'min:1', 'max:'.count($locales)],
            'settings.enabled_locales.*' => ['required', 'string', 'distinct:strict', Rule::in($locales)],
        ];
    }

    public function read(): array
    {
        return $this->resolve($this->stored());
    }

    public function stored(): array
    {
        return PlatformSetting::query()->whereKey(self::KEYS)->get()->pluck('value', 'name')->all();
    }

    public function resolve(array $stored): array
    {
        $invalid = Validator::make(['settings' => $stored], self::rules())->errors();

        return [
            'platform_name' => $invalid->has('settings.platform_name') ? config('app.name') : $stored['platform_name'],
            ...$this->locales($stored, $invalid->has('settings.enabled_locales') || $invalid->has('settings.enabled_locales.*')),
        ];
    }

    private function locales(array $stored, bool $invalidEnabled): array
    {
        $enabled = $invalidEnabled ? array_keys(config('app.supported_locales')) : $stored['enabled_locales'];
        $default = $stored['default_locale'] ?? null;

        if (! in_array($default, $enabled, true)) {
            $default = config('app.locale');
        }

        if (! in_array($default, $enabled, true)) {
            $default = config('app.fallback_locale');
        }

        return [
            'default_locale' => in_array($default, $enabled, true)
                ? $default
                : (in_array('en', $enabled, true) ? 'en' : $enabled[0]),
            'enabled_locales' => $enabled,
        ];
    }
}
