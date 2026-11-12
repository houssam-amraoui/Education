<?php

namespace App\Actions\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SaveRole
{
    public function __construct(private AccountAccessTransaction $transaction) {}

    public function handle(User $actor, array $attributes, array $abilities, ?Role $role = null): Role
    {
        try {
            return $this->transaction->run(function () use ($actor, $attributes, $abilities, $role): Role {
                $current = $role === null ? new Role : Role::findOrFail($role->id);
                $gate = Gate::forUser($actor);
                if ($current->exists) {
                    $gate->authorize('update', $current);
                }
                $gate->authorize('defineAbilities', [Role::class, $abilities]);

                $current->fill($attributes)->save();

                $existing = $current->abilities()->pluck('ability')->all();
                $current->abilities()->whereNotIn('ability', $abilities)->delete();
                foreach (array_diff($abilities, $existing) as $ability) {
                    $current->abilities()->create(['ability' => $ability]);
                }

                return $current->load('abilities');
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => __('validation.unique', ['attribute' => 'name'])]);
        }
    }
}
