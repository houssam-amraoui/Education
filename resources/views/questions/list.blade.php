@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="row between wrap">
        <a class="button button-primary" href="{{ route($prefix.'.create', $owner) }}">Add question</a>
    </div>
    @if ($records->isEmpty())
        <div class="empty-state"><h2>No questions yet</h2><p>Add a question to start the list.</p></div>
    @else
        <form class="filter-bar" data-filter-form>
            <div class="field grow">
                <label for="question-search">Find a record</label>
                <input type="search" id="question-search" placeholder="Search questions" data-content-search>
            </div>
            <div class="field">
                <label for="question-type-filter">Question type</label>
                <select id="question-type-filter" data-content-type>
                    <option value="">All types</option>
                    @foreach (App\Support\QuestionType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="button button-secondary">Search</button>
        </form>
        <div class="table-scroll" tabindex="0" role="region" aria-label="{{ $screen['title'] }}">
            <table>
                <caption class="sr-only">{{ $screen['title'] }}</caption>
                <thead><tr><th scope="col">Name</th><th scope="col">Context</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
                <tbody data-filter-list data-live-question-list>
                    @foreach ($records as $question)
                        <tr data-filter-item data-filter-type="{{ $question->type->value }}">
                            <td><strong dir="auto">{{ $question->prompt }}</strong></td>
                            <td>{{ $question->type->label() }} · {{ $question->points }} points</td>
                            <td><x-prototype.badge tone="success">Ready</x-prototype.badge></td>
                            <td><div class="actions">
                                <a class="table-action" href="{{ route($prefix.'.edit', [$owner, $question]) }}">Edit <span class="sr-only">{{ $question->prompt }}</span></a>
                                @foreach (['up' => -1, 'down' => 1] as $direction => $offset)
                                    <form method="post" action="{{ route($prefix.'.reorder', [$owner, $question]) }}" data-live-form>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="position" value="{{ $question->position + $offset }}">
                                        <button class="button button-tertiary" data-pending="Moving…" aria-label="Move {{ $question->prompt }} {{ $direction }}" @disabled($offset < 0 ? $loop->parent->first : $loop->parent->last)>Move {{ $direction }}</button>
                                    </form>
                                @endforeach
                                <form method="post" action="{{ route($prefix.'.destroy', [$owner, $question]) }}" data-live-form>
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button-tertiary" data-pending="Removing…" aria-label="Remove {{ $question->prompt }}">Remove</button>
                                </form>
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="empty-state" data-empty-state hidden><h2>No matching records</h2><p>Try another name or question type.</p><button class="button button-secondary" data-reset-filters>Reset filters</button></div>
        <p class="results-meta" data-result-count role="status">{{ $records->count() }} questions</p>
    @endif
@endsection
