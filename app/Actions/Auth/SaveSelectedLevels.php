<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveSelectedLevels
{
    public function handle(User $student, array $levelIds): void
    {
        DB::transaction(function () use ($student, $levelIds): void {
            $current = User::lockForUpdate()->findOrFail($student->getKey());
            $current->selectedLevels()->sync($levelIds);
        });
    }
}
