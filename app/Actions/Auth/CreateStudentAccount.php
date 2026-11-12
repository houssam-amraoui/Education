<?php

namespace App\Actions\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateStudentAccount
{
    public function handle(array $identity): User
    {
        return DB::transaction(function () use ($identity): User {
            $student = Role::where('key', Role::STUDENT)->firstOrFail();
            $identity['password'] = Hash::make($identity['password']);
            try {
                $user = User::create($identity);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => 'email'])]);
            }
            $user->roles()->attach($student);

            return $user;
        });
    }
}
