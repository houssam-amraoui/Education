@php
    $chosen = $errors->any() ? old('level_ids', []) : [...$selected, $suggestedLevelId];
    $chosen = array_filter((array) $chosen, fn ($id) => is_int($id) || is_string($id));
    $hasSelectionErrors = $errors->has('level_ids') || $errors->has('level_ids.*');
@endphp
@if($levels->isEmpty())
    <div class="empty-state"><h2>No learning paths are available yet</h2></div>
    <x-prototype.link :href="$url('S01')">Continue to dashboard</x-prototype.link>
@else
    <form method="post" action="{{ route('account.learning-goals.update') }}" class="stack" data-live-form>
        @csrf
        @method('PUT')
        @if($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
        <fieldset class="stack" id="level_ids" tabindex="-1" @if($hasSelectionErrors) aria-invalid="true" @endif>
            <legend class="sr-only">Learning paths</legend>
            <div class="cards three">
                @foreach($levels as $level)
                    <label class="card choice-card">
                        <span class="tile-icon"><x-prototype.icon/></span>
                        <strong dir="auto">{{ $level->title }}</strong>
                        @if($level->description)<span class="muted" dir="auto">{{ $level->description }}</span>@endif
                        <span class="choice"><input type="checkbox" name="level_ids[]" value="{{ $level->id }}" @checked(in_array($level->id, $chosen))> Select this learning path</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div class="actions">
            <button class="button button-primary" data-pending="Saving…">Save learning paths</button>
            <x-prototype.link :href="$url('S01')">Continue to dashboard</x-prototype.link>
        </div>
    </form>
@endif
