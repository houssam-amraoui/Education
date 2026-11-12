<?php

namespace App\Actions\Auth;

use App\Models\Role;
use Closure;
use Illuminate\Support\Facades\DB;

class AccountAccessTransaction
{
    public function run(Closure $operation): mixed
    {
        return DB::transaction(function () use ($operation): mixed {
            // Serialize authority changes with writes that depend on the actor's current authority.
            Role::where('key', Role::ADMINISTRATOR)->lockForUpdate()->firstOrFail();

            return $operation();
        }, 3);
    }
}
