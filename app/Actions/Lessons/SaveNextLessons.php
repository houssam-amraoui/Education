<?php

namespace App\Actions\Lessons;

use App\Models\Lesson;
use App\Support\CurriculumStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveNextLessons
{
    public function handle(Lesson $lesson, array $targetIds): void
    {
        $targetIds = array_map('intval', $targetIds);

        if (in_array($lesson->id, $targetIds, true)) {
            throw ValidationException::withMessages(['next_lesson_ids' => 'A lesson cannot link to itself.']);
        }

        DB::transaction(function () use ($lesson, $targetIds): void {
            $locked = Lesson::query()
                ->whereKey([$lesson->id, ...$targetIds])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $source = $locked->get($lesson->id);
            abort_if($source === null, 404);

            foreach ($targetIds as $targetId) {
                $target = $locked->get($targetId);
                if ($target === null || $target->status === CurriculumStatus::Archived) {
                    throw ValidationException::withMessages(['next_lesson_ids' => 'Select existing lessons that are not archived.']);
                }
            }

            $source->nextLessons()->sync($targetIds);
        }, 3);
    }
}
