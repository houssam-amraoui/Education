@php
    $editing = $record->exists;
@endphp
<div class="split">
    <form method="post" action="{{ $editing ? route('admin.accounts.users.update', $record) : route('admin.accounts.users.store') }}" class="stack" data-live-form>
        @csrf
        @if($editing) @method('PUT') @endif
        <x-prototype.card>
            <h2>{{ $editing ? 'Edit user' : 'Create user' }}</h2>
            <x-account.field label="Name" name="name" :value="$record->name" :required="true" maxlength="255"/>
            <x-account.field label="Email address" name="email" type="email" :value="$record->email" :required="true" maxlength="255"/>
            <x-account.field label="Password" name="password" type="password" :required="!$editing" minlength="8" autocomplete="new-password"
                :help="$editing ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.'"/>
            <x-account.field label="Confirm password" name="password_confirmation" type="password" :required="!$editing" autocomplete="new-password"/>
        </x-prototype.card>
        <div class="actions">
            <button class="button button-primary" data-pending="Saving user…">Save user</button>
            <a class="button button-secondary" href="{{ $url('A02') }}">Cancel</a>
        </div>
    </form>
    <aside class="stack">
        <x-prototype.card>
            <h3>Record settings</h3>
            @if(!$editing)
                <p>New accounts start with the Student role.</p>
            @elseif($assignable === null)
                <p>Roles</p>
                <p>{{ $record->roles->pluck('name')->implode(', ') ?: 'No roles assigned.' }}</p>
            @else
                @php
                    $selected = (array) old('role_ids', $record->roles->modelKeys());
                    $error = $errors->first('role_ids') ?: $errors->first('role_ids.*');
                    $ownAccount = auth()->id() === $record->id;
                @endphp
                <form method="post" action="{{ route('admin.accounts.users.roles.update', $record) }}" class="stack" data-live-form>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="roles_present" value="1">
                    <fieldset id="role_ids" tabindex="-1" @if($error) aria-invalid="true" aria-describedby="role-ids-error" @endif>
                        <legend>Roles</legend>
                        @forelse($assignable as $role)
                            @php($locked = $ownAccount && $role->key === App\Models\Role::ADMINISTRATOR && $record->roles->contains($role))
                            @if($locked)<input type="hidden" name="role_ids[]" value="{{ $role->id }}">@endif
                            <label class="choice"><input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                                @checked($locked || in_array($role->id, $selected)) @disabled($locked)> <bdi>{{ $role->name }}</bdi></label>
                        @empty
                            <p>No assignable roles.</p>
                        @endforelse
                    </fieldset>
                    @if($error)<p id="role-ids-error" class="field-error">{{ $error }}</p>@endif
                    <button class="button button-secondary" data-pending="Saving roles…">Save roles</button>
                </form>
            @endif
        </x-prototype.card>
    </aside>
</div>
