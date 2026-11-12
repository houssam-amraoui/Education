<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UpdateUserAccount
{
    public function __construct(private AccountAccessTransaction $transaction) {}

    public function handle(User $actor, User $user, array $identity): User
    {
        try {
            return $this->transaction->run(function () use ($actor, $user, $identity): User {
                $current = User::findOrFail($user->id);
                Gate::forUser($actor)->authorize('update', $current);

                if (isset($identity['password'])) {
                    $identity['password'] = Hash::make($identity['password']);
                }
                $current->fill($identity);
                if ($current->isDirty('email')) {
                    $current->email_verified_at = null;
                }
                $current->save();

                return $current;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => 'email'])]);
        }
    }
}
