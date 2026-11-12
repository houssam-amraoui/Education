<?php

namespace App\Actions\Resources;

use App\Models\Resource;
use App\Support\CurriculumStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeResourceStatus
{
    public function handle(Resource $resource, CurriculumStatus $status): void
    {
        DB::transaction(function () use ($resource, $status): void {
            $current = Resource::query()->lockForUpdate()->findOrFail($resource->getKey());
            if ($current->status === $status) {
                return;
            }

            if ($current->status === CurriculumStatus::Archived) {
                throw ValidationException::withMessages(['status' => 'Archived resources cannot change status.']);
            }

            if ($status === CurriculumStatus::Published && $current->stored_file_id === null) {
                throw ValidationException::withMessages(['status' => 'Upload a file before publishing.']);
            }

            $current->status = $status;
            $current->save();
        }, 3);
    }
}
