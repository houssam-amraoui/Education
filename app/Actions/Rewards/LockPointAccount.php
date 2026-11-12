<?php

namespace App\Actions\Rewards;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class LockPointAccount
{
    // Take the account lock before any ordinary read in the write transaction.
    public function handle(User $student): void
    {
        $account = User::query()->whereKey($student->getKey());

        if (DB::getDriverName() === 'sqlite') {
            // SQLite has no row locks. Acquire its writer lock before reading the balance.
            DB::table('users')->where('id', $student->getKey())->update(['id' => DB::raw('id')]);
        }

        $account->lockForUpdate()->firstOrFail();
    }
}
