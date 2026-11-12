@php
    $editing = $record->exists;
    $selected = (array) old('abilities', $record->abilities->pluck('ability')->all());
    $error = $errors->first('abilities') ?: $errors->first('abilities.*');
    $labels = [
        'admin.enter' => 'Enter administration',
        'users.manage' => 'Manage users',
        'roles.manage' => 'Manage roles',
        'roles.assign' => 'Assign roles',
    ];
@endphp
<form method="post" action="{{ $editing ? route('admin.accounts.roles.update', $record) : route('admin.accounts.roles.store') }}" class="split" data-live-form>
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="stack">
        <x-prototype.card>
            <h2>{{ $editing ? 'Edit role' : 'Create role' }}</h2>
            <x-account.field label="Name" name="name" :value="$record->name" :required="true" maxlength="255"/>
            <x-account.field label="Description" name="description" type="textarea" :value="$record->description" maxlength="1000"/>
            <input type="hidden" name="abilities_present" value="1">
            <fieldset id="abilities" tabindex="-1" @if($error) aria-invalid="true" aria-describedby="abilities-error" @endif>
                <legend>Abilities</legend>
                @foreach($abilities as $ability)
                    <label class="choice"><input type="checkbox" name="abilities[]" value="{{ $ability->value }}"
                        @checked(in_array($ability->value, $selected, true))> {{ $labels[$ability->value] }}</label>
                @endforeach
            </fieldset>
            @if($error)<p id="abilities-error" class="field-error">{{ $error }}</p>@endif
        </x-prototype.card>
        <div class="actions">
            <button class="button button-primary" data-pending="Saving role…">Save role</button>
            <a class="button button-secondary" href="{{ $url('A05') }}">Cancel</a>
        </div>
    </div>
    <aside class="stack">
        <x-prototype.card>
            <h3>Record settings</h3>
            <x-prototype.badge>{{ $record->key ? 'Built-in' : 'Custom' }}</x-prototype.badge>
            <p>Saving changes updates the abilities for accounts assigned to this role.</p>
        </x-prototype.card>
    </aside>
</form>
