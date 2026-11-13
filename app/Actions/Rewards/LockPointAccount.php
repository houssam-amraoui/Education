<?php

namespace App\Actions\Rewards;

use App\Models\User;

class LockPointAccount
{
    // Take the account lock before any ordinary read in the write transaction.
    public function handle(User $student): void
    {
        User::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();
    }
}
