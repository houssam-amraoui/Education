<?php

namespace App\Actions\Curriculum;

use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeCurriculumStatus
{
    public function handle(Builder $scope, int $id, CurriculumStatus $status): void
    {
        DB::transaction(function () use ($scope, $id, $status): void {
            $item = (clone $scope)->lockForUpdate()->findOrFail($id);

            if ($item->status === $status) {
                return;
            }

            if ($item->status === CurriculumStatus::Archived) {
                throw ValidationException::withMessages(['status' => 'Archived content cannot change status.']);
            }

            $item->status = $status;
            $item->save();
        }, 3);
    }
}
