@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    @include('curriculum.feedback')
    <div class="split">
        <form method="post" class="stack" action="{{ $rule->exists ? route('admin.access-rules.update', $rule) : route('admin.access-rules.store') }}" data-live-form>
            @csrf
            @if($rule->exists) @method('PUT') @endif
            <x-prototype.card>
                <h2>{{ $rule->exists ? 'Edit access rule' : 'Create access rule' }}</h2>
                <div class="field"><label for="target_id">Target</label>
                    <select id="target_id" name="target" required @if($errors->has('target_id') || $errors->has('target_type')) aria-invalid="true" aria-describedby="target-error" @endif>
                        <option value="">Choose content or a feature</option>
                        @foreach($targets as $type => $options)
                            @if($options)
                                <optgroup label="{{ str($type)->headline() }}">
                                    @foreach($options as $id => $name)<option value="{{ "$type:$id" }}" @selected(old('target', $target) === "$type:$id")>{{ $name }}</option>@endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    @if($errors->has('target_id') || $errors->has('target_type'))<small class="field-error" id="target-error">{{ $errors->first('target_id') ?: $errors->first('target_type') }}</small>@endif
                </div>
                <div class="field"><label for="requirement">Access classification</label>
                    <select id="requirement" name="requirement" required @error('requirement') aria-invalid="true" aria-describedby="requirement-error" @enderror>
                        @foreach(App\Support\AccessTarget::REQUIREMENTS as $requirement)<option value="{{ $requirement }}" @selected(old('requirement', $rule->requirement) === $requirement)>{{ __("library.access.$requirement") }}</option>@endforeach
                    </select>
                    @error('requirement')<small class="field-error" id="requirement-error">{{ $message }}</small>@enderror
                </div>
                @foreach(['level' => $levels, 'lesson' => $lessons] as $kind => $options)
                    @php($field = "required_{$kind}_id")
                    <div class="field"><label for="{{ $field }}">{{ $kind === 'level' ? 'Required learning level' : 'Prerequisite lesson' }}</label>
                        <select id="{{ $field }}" name="{{ $field }}" @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                            <option value="">None</option>
                            @foreach($options as $id => $title)<option value="{{ $id }}" @selected((string) old($field, $rule->{$field}) === (string) $id)>{{ $title }}</option>@endforeach
                        </select>
                        @error($field)<small class="field-error" id="{{ $field }}-error">{{ $message }}</small>@enderror
                    </div>
                @endforeach
                <div class="actions">
                    <button class="button button-primary" data-pending="Saving…">Save access rule</button>
                    <x-prototype.link :href="route('admin.access-rules.index')">Cancel</x-prototype.link>
                </div>
            </x-prototype.card>
        </form>
        <aside class="stack">
            <x-prototype.card>
                <h3>Points cost (if applicable)</h3>
                <p>{{ $cost ? trans_choice(':count point|:count points', $cost->points).($cost->enabled ? '' : ' · Disabled') : 'No points cost is configured for this rule.' }}</p>
                @can('rewards.manage')
                    <x-prototype.link :href="$cost ? route('admin.rewards.costs.edit', $cost) : route('admin.rewards.costs.create')">Configure points cost</x-prototype.link>
                @endcan
            </x-prototype.card>
        </aside>
    </div>
@endsection
