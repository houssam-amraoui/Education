<?php

namespace App\Actions\Auth;

use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveSelectedLevels
{
    public function handle(User $student, array $levelIds): void
    {
        DB::transaction(function () use ($student, $levelIds): void {
            $current = User::lockForUpdate()->findOrFail($student->getKey());
            $levels = Level::publishedInCurriculum()->whereKey($levelIds)->sharedLock()->get(['id']);

            if ($levels->count() !== count($levelIds)) {
                throw ValidationException::withMessages([
                    'level_ids' => __('Select distinct published learning paths.'),
                ]);
            }

            $current->selectedLevels()->sync($levels->modelKeys());
        });
    }
}
