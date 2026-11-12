@extends('prototype.layout')

@section('content')
    @php
        $streakForm = $screen['id'] === 'A37';
        $isRule = $kind === 'rule';
        $isCost = $kind === 'cost';
        $event = old('event', $record?->event?->value ?? App\Support\RewardEvent::LessonCompletion->value);
        $retryDefault = in_array($event, ['exercise_result', 'exam_completion'], true) && $record
            ? (int) $record->allow_retries : null;
        $prefix = 'admin.reward-configuration.'.match ($kind) {
            'rule' => 'point-rules', 'cost' => 'point-costs', 'achievement' => 'achievements',
        };
        $action = $record ? route($prefix.'.update', $record) : route($prefix.'.store');
    @endphp
    @include('curriculum.heading')
    @include('curriculum.feedback')
    @if($streakForm)
        <div class="actions">
            <x-prototype.link :href="$url('A37')">Create milestone</x-prototype.link>
            @foreach($milestones as $milestone)
                <x-prototype.link :href="route('admin.rewards.streak.edit', $milestone)" :variant="$milestone->is($record) ? 'primary' : 'secondary'"><bdi>{{ $milestone->name }}</bdi> · {{ $milestone->streak_days }} days</x-prototype.link>
            @endforeach
        </div>
    @endif
    <form method="post" action="{{ $action }}" class="split" data-live-form>
        @csrf
        @if($record)@method('PUT')@endif
        @if($streakForm)
            <input type="hidden" name="event" value="streak_milestone">
        @endif
        <div class="stack">
            <x-prototype.card>
                <div class="row between"><h2>{{ $record ? 'Edit' : 'Create' }} {{ $streakForm ? 'streak milestone' : strtolower($screen['title']) }}</h2></div>
                @if(!$isCost)
                    <x-prototype.field label="Title" name="name" :value="old('name', $record?->name)" required maxlength="255" :error="$errors->first('name')"/>
                @endif
                @if($kind === 'achievement')
                    <x-prototype.field label="Description" name="description" type="textarea" :value="old('description', $record?->description)" required maxlength="4000" :error="$errors->first('description')"/>
                @endif
                @if(!$streakForm && $kind !== 'achievement')
                    @if($record)
                        <p>Rule direction: {{ $isCost ? 'Spend points' : 'Earn points' }}</p>
                    @else
                        <label class="field" for="point-direction">Rule direction
                            <select id="point-direction" data-reward-direction>
                                <option value="{{ route('admin.rewards.rules.create') }}" @selected(!$isCost)>Earn points</option>
                                <option value="{{ route('admin.rewards.costs.create') }}" @selected($isCost)>Spend points</option>
                            </select>
                        </label>
                        <noscript><a href="{{ route($isCost ? 'admin.rewards.rules.create' : 'admin.rewards.costs.create') }}">{{ $isCost ? 'Earn points' : 'Spend points' }}</a></noscript>
                    @endif
                @endif
                @if($isCost)
                    @if($record)
                        <p>Unlock target: <bdi>{{ $targetName }}</bdi></p>
                    @else
                        <label class="field" for="target_id">Unlock target (required)
                            <select id="target_id" name="target_id" required @error('target_id') aria-invalid="true" aria-describedby="target_id-error" @enderror>
                                <option value="">Select content or a feature</option>
                                @foreach($targets as $label => $options)
                                    <optgroup label="{{ $label }}">
                                        @foreach($options as $key => $title)
                                            <option value="{{ $key }}" @selected(old('target_type').':'.old('target_id') === $key)>{{ $title }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('target_id')<small class="field-error" id="target_id-error">{{ $message }}</small>@enderror
                        </label>
                    @endif
                @elseif(!$streakForm)
                    <label class="field" for="event">{{ $isRule ? 'Qualifying activity' : 'Achievement criterion' }}
                        <select id="event" name="event" required data-reward-event @error('event') aria-invalid="true" aria-describedby="event-error" @enderror>
                            @foreach(App\Support\RewardEvent::cases() as $option)
                                <option value="{{ $option->value }}" @selected($event === $option->value)>{{ $isRule ? $option->label() : ucfirst($option->criterion()) }}</option>
                            @endforeach
                        </select>
                        @error('event')<small class="field-error" id="event-error">{{ $message }}</small>@enderror
                    </label>
                    <div data-reward-events="exercise_result">
                        <x-prototype.field label="Minimum exercise score (%)" name="minimum_score_percent" type="number" min="0" max="100"
                            :value="old('minimum_score_percent', $record?->minimum_score_percent)" :error="$errors->first('minimum_score_percent')"/>
                    </div>
                    @if($isRule)
                        <div data-reward-events="exercise_result exam_completion">
                            <label class="field" for="allow_retries">Retry eligibility
                                <select id="allow_retries" name="allow_retries" @error('allow_retries') aria-invalid="true" aria-describedby="allow_retries-error" @enderror>
                                    <option value="">Select for assessments</option>
                                    <option value="0" @selected((string) old('allow_retries', $retryDefault) === '0')>First submission only</option>
                                    <option value="1" @selected((string) old('allow_retries', $retryDefault) === '1')>Include retries</option>
                                </select>
                                @error('allow_retries')<small class="field-error" id="allow_retries-error">{{ $message }}</small>@enderror
                            </label>
                        </div>
                    @endif
                @endif
                @if($isRule)
                    <div @unless($streakForm) data-reward-events="streak_milestone" @endunless>
                        <x-prototype.field label="Learning days for a streak milestone" name="streak_days" type="number" min="1" max="3660" :required="$streakForm"
                            :value="old('streak_days', $record?->streak_days)" :error="$errors->first('streak_days')"/>
                    </div>
                @endif
                @if($kind === 'achievement')
                    <x-prototype.field label="Target" name="required_count" type="number" min="1" max="3660" required
                        :value="old('required_count', $record?->required_count)" :error="$errors->first('required_count')"/>
                    <x-prototype.field label="Reward points" name="reward_points" type="number" min="0" max="1000000" required
                        :value="old('reward_points', $record?->reward_points ?? 0)" :error="$errors->first('reward_points')"/>
                @else
                    <x-prototype.field :label="$streakForm ? 'Milestone reward (points)' : 'Points value or cost'" name="points" type="number" min="1" max="1000000" required
                        :value="old('points', $record?->points)" :error="$errors->first('points')"/>
                @endif
            </x-prototype.card>
            <div class="actions">
                <button class="button button-primary" data-pending="Saving…">Save {{ $streakForm ? 'milestone' : strtolower($screen['title']) }}</button>
                <x-prototype.link :href="$url($parent)">Cancel</x-prototype.link>
            </div>
        </div>
        <aside class="stack">
            <x-prototype.card>
                <h3>Record settings</h3>
                <label class="field" for="enabled">State
                    <select id="enabled" name="enabled" @error('enabled') aria-invalid="true" aria-describedby="enabled-error" @enderror>
                        <option value="0" @selected((string) old('enabled', ($record?->enabled ?? false) ? '1' : '0') === '0')>Disabled</option>
                        <option value="1" @selected((string) old('enabled', ($record?->enabled ?? false) ? '1' : '0') === '1')>Enabled</option>
                    </select>
                    @error('enabled')<small class="field-error" id="enabled-error">{{ $message }}</small>@enderror
                </label>
                @if($streakForm)<p>Learning days start at midnight UTC.</p>@endif
            </x-prototype.card>
        </aside>
    </form>
@endsection
