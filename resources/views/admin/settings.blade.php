@extends('prototype.layout')

@section('content')
    @php
        $labels = ['en' => 'English', 'fr' => 'Français', 'ar' => 'العربية'];
        $languages = array_intersect_key($labels, config('app.supported_locales'));
        $name = old('settings.platform_name', $settings['platform_name']);
        $default = old('settings.default_locale', $settings['default_locale']);
        $enabled = session()->hasOldInput() ? old('settings.enabled_locales', []) : $settings['enabled_locales'];
        $enabled = is_array($enabled) ? $enabled : [];
        $localeError = $errors->first('settings.default_locale');
        $languagesError = $errors->first('settings.enabled_locales') ?: $errors->first('settings.enabled_locales.*');
    @endphp
    <div class="page-heading">
        <div>
            <span class="eyebrow">Teaching workspace</span>
            <h1>{{ $screen['title'] }}</h1>
            <p>Organize the next step in your students’ learning.</p>
        </div>
    </div>
    @if(session('status'))
        <p class="notice success" role="status">{{ session('status') }}</p>
    @endif
    @if($errors->any())
        <div class="validation-summary" role="alert" tabindex="-1" data-live-errors>
            <strong>{{ __('shared.feedback.validation_summary') }}</strong>
            @error('settings')<p>{{ $message }}</p>@enderror
        </div>
    @endif
    <div class="split">
        <form method="post" action="{{ route('admin.settings.update') }}" class="stack card" data-live-form>
            @csrf
            @method('PUT')
            <h2>Platform preferences</h2>
            <label class="field" for="preference-language">Interface language
                <select id="preference-language" name="settings[default_locale]" aria-describedby="preference-language-help{{ $localeError ? ' preference-language-error' : '' }}" @if($localeError) aria-invalid="true" @endif>
                    @foreach($languages as $code => $label)
                        <option value="{{ $code }}" lang="{{ $code }}" @selected($default === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <small id="preference-language-help">Choose the default from the available interface languages.</small>
                @if($localeError)<small class="field-error" id="preference-language-error">{{ $localeError }}</small>@endif
            </label>
            <x-prototype.field label="Platform name" name="platform-name" :value="is_string($name) ? $name : ''"
                :error="$errors->first('settings.platform_name')" maxlength="255" field-name="settings[platform_name]" />
            <fieldset @if($languagesError) aria-describedby="enabled-locales-error" aria-invalid="true" @endif>
                <legend>Available interface languages</legend>
                @foreach($languages as $code => $label)
                    <label class="choice"><input type="checkbox" name="settings[enabled_locales][]" value="{{ $code }}" @checked(in_array($code, $enabled, true))><span lang="{{ $code }}">{{ $label }}</span></label>
                @endforeach
                @if($languagesError)<small class="field-error" id="enabled-locales-error">{{ $languagesError }}</small>@endif
            </fieldset>
            <div class="actions"><button class="button button-primary" data-pending="Saving changes…">Save changes</button></div>
        </form>
        <x-prototype.card>
            <h3>Current settings</h3>
            <p><bdi>{{ $settings['platform_name'] }}</bdi> · <bdi lang="{{ $settings['default_locale'] }}">{{ $languages[$settings['default_locale']] }}</bdi></p>
            @unless($hasSavedSettings)<p>No platform settings have been saved. Configuration defaults are in use.</p>@endunless
        </x-prototype.card>
    </div>
@endsection
