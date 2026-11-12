@php
    $editingProfile = $screen['id'] === 'S22';
    $localeLabels = ['en' => 'English', 'fr' => 'Français', 'ar' => 'العربية'];
    $localeLabels = array_intersect_key($localeLabels, config('app.supported_locales'));
    $language = old('locale', $student->locale ?? app()->getLocale());
    $studyGoal = old('daily_study_goal_minutes', $student->daily_study_goal_minutes);
@endphp
<div class="split">
    <form method="post" action="{{ route($editingProfile ? 'account.profile.update' : 'account.preferences.update') }}" class="stack card" data-live-form>
        @csrf
        @method('PUT')
        @if($editingProfile)
            <div class="row">
                <span class="avatar large" aria-hidden="true">{{ Str::upper(collect(preg_split('/\s+/u', trim($student->name)))->take(2)->map(fn ($part) => Str::substr($part, 0, 1))->implode('')) }}</span>
                <div><h2 dir="auto">{{ $student->name }}</h2><small>Student</small></div>
            </div>
            <x-account.field label="Full name" name="name" :value="$student->name" :required="true" autocomplete="name" maxlength="255" dir="auto"/>
            <x-account.field label="Email address" name="email" type="email" :value="$student->email" :required="true" autocomplete="email" maxlength="255" dir="ltr"/>
            <x-account.field label="City" name="city" :value="$student->city" autocomplete="address-level2" maxlength="255" dir="auto"/>
        @else
            <h2>Make learning feel like you</h2>
            <label class="field" for="locale">Interface language (required)
                <select id="locale" name="locale" required @error('locale') aria-invalid="true" aria-describedby="locale-error" @enderror>
                    @if(! is_string($language) || ! isset($localeLabels[$language]))
                        <option value="" selected disabled>Choose a language</option>
                    @endif
                    @foreach($localeLabels as $code => $label)
                        <option value="{{ $code }}" lang="{{ $code }}" @selected($language === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('locale')<small id="locale-error" class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field" for="daily_study_goal_minutes">Daily study goal (required)
                <select id="daily_study_goal_minutes" name="daily_study_goal_minutes" required
                    @error('daily_study_goal_minutes') aria-invalid="true" aria-describedby="daily_study_goal_minutes-error" @enderror>
                    <option value="" disabled @selected(! in_array($studyGoal, [15, 30, 45, '15', '30', '45'], true))>Choose a daily study goal</option>
                    @foreach([15, 30, 45] as $minutes)
                        <option value="{{ $minutes }}" @selected($studyGoal === $minutes || $studyGoal === (string) $minutes)>{{ $minutes }} minutes</option>
                    @endforeach
                </select>
                @error('daily_study_goal_minutes')<small id="daily_study_goal_minutes-error" class="field-error">{{ $message }}</small>@enderror
            </label>
            <x-account.field label="Learning focus" name="learning_focus" :value="$student->learning_focus" maxlength="255" dir="auto"/>
        @endif
        <div class="actions">
            <button class="button button-primary" data-pending="Saving changes…">Save changes</button>
        </div>
    </form>
    <x-prototype.card>
        <h3>{{ $editingProfile ? 'Your learning preferences' : 'Your learning paths' }}</h3>
        @if($editingProfile)
            @if(isset($localeLabels[$student->locale]))
                <p>Interface language <bdi lang="{{ $student->locale }}">{{ $localeLabels[$student->locale] }}</bdi></p>
            @endif
            @if($student->daily_study_goal_minutes !== null)
                <p>Daily study goal {{ $student->daily_study_goal_minutes }} minutes</p>
            @endif
            @if($student->learning_focus !== null)
                <p dir="auto">{{ $student->learning_focus }}</p>
            @endif
            @if($student->locale === null && $student->daily_study_goal_minutes === null && $student->learning_focus === null)
                <p>No learning preferences saved yet.</p>
            @endif
            <a href="{{ $url('S23') }}" class="button button-secondary">Open settings</a>
        @else
            <p>Learning path selection is currently unavailable.</p>
            <a href="{{ $url('S01') }}" class="button button-secondary">Back to your account</a>
        @endif
    </x-prototype.card>
</div>
