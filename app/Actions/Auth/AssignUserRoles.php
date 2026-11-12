<?php

namespace App\Actions\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignUserRoles
{
    public function __construct(private AccountAccessTransaction $transaction) {}

    public function handle(User $actor, User $user, array $roleIds): void
    {
        $this->transaction->run(function () use ($actor, $user, $roleIds): void {
            $current = User::findOrFail($user->id);
            $roles = Role::with('abilities')->findMany($roleIds);
            if ($roles->count() !== count($roleIds)) {
                throw ValidationException::withMessages(['role_ids' => __('validation.exists', ['attribute' => 'role IDs'])]);
            }

            Gate::forUser($actor)->authorize('assignRoles', [$current, $roles]);
            $current->roles()->sync($roles->modelKeys());
        });
    }
}
