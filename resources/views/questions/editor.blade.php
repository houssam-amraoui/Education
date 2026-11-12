@extends('prototype.layout')

@section('content')
    @php($selectedType = old('type', $question->type->value))
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <form class="split" method="post" action="{{ $question->exists ? route($prefix.'.update', [$owner, $question]) : route($prefix.'.store', $owner) }}" data-live-form data-question-authoring>
        @csrf
        @if ($question->exists) @method('PUT') @endif
        <div class="stack">
            <x-prototype.card>
                <div class="row between"><h2>{{ $question->exists ? 'Edit' : 'Create' }} {{ strtolower($screen['title']) }}</h2></div>
                <div class="field">
                    <label for="type">Question type</label>
                    <select id="type" name="type" data-authoring-type required @error('type') aria-invalid="true" @enderror>
                        @foreach (App\Support\QuestionType::cases() as $type)
                            <option value="{{ $type->value }}" @selected($type->value === $selectedType)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <x-account.field label="Question prompt" name="prompt" type="textarea" :value="$question->prompt" required maxlength="10000" dir="auto"/>
                <div id="content" data-authoring-fields data-definition="{{ json_encode($definition, JSON_INVALID_UTF8_SUBSTITUTE) }}">
                    <p role="status">Loading answer fields…</p>
                </div>
                <noscript><p class="notice info">Enable JavaScript to edit the question’s answer definition.</p></noscript>
                <x-account.field label="Points" name="points" type="number" :value="$question->points" required min="0" max="65535"/>
                <x-account.field label="Correction explanation" name="explanation" type="textarea" :value="$question->explanation" maxlength="10000" dir="auto"/>
            </x-prototype.card>
            <div class="actions">
                <button class="button button-primary" data-pending="Saving…" data-authoring-save disabled>Save {{ strtolower($screen['title']) }}</button>
                <a class="button button-secondary" href="{{ route($prefix.'.index', $owner) }}">Cancel</a>
            </div>
        </div>
        <aside class="stack">
            <x-prototype.card><h3>Record settings</h3><p>Questions follow the publication state of their assessment.</p></x-prototype.card>
        </aside>
    </form>
@endsection
