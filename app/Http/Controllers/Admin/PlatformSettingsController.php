<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdatePlatformSettingsRequest;
use App\Models\PlatformSetting;
use App\Support\AccountPages;
use App\Support\PlatformSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlatformSettingsController
{
    public function show(PlatformSettings $settings): View
    {
        $stored = $settings->stored();

        return AccountPages::view('A43', 'settings', [
            'parent' => 'A01',
            'settings' => $settings->resolve($stored),
            'hasSavedSettings' => $stored !== [],
        ], 'admin.settings');
    }

    public function update(UpdatePlatformSettingsRequest $request): JsonResponse|RedirectResponse
    {
        $settings = $request->validated('settings');
        $rows = [];

        foreach (PlatformSettings::KEYS as $name) {
            $rows[] = ['name' => $name, 'value' => json_encode($settings[$name], JSON_THROW_ON_ERROR)];
        }

        PlatformSetting::upsert($rows, ['name'], ['value']);

        return AccountPages::html($request)
            ? redirect()->route('admin.settings.show', status: 303)->with('status', 'Platform settings saved.')
            : response()->json($settings);
    }
}
