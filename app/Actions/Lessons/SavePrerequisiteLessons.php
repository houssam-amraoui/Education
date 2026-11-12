<?php

namespace App\Actions\Lessons;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Support\CurriculumStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SavePrerequisiteLessons
{
    public function handle(Lesson|Exercise|Exam $content, array $targetIds): void
    {
        Validator::make(['prerequisite_lesson_ids' => $targetIds], [
            'prerequisite_lesson_ids' => ['list', 'max:100'],
            'prerequisite_lesson_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])->validate();

        $targetIds = array_map('intval', $targetIds);
        abort_unless($content->exists, 404);

        DB::transaction(function () use ($content, $targetIds): void {
            $lessons = Lesson::query()->orderBy('id')->lockForUpdate();

            // Lesson edits take the same locks before reading the graph, including
            // when no edges exist yet. Assessments cannot introduce graph cycles.
            if (! $content instanceof Lesson) {
                $lessons->whereKey($targetIds);
            }

            $locked = $lessons->get(['id', 'status'])->keyBy('id');
            $source = $content instanceof Lesson
                ? $locked->get($content->getKey())
                : $content->newQuery()->lockForUpdate()->find($content->getKey());
            abort_if($source === null, 404);

            foreach ($targetIds as $id) {
                if ($source instanceof Lesson && $source->id === $id) {
                    throw ValidationException::withMessages(['prerequisite_lesson_ids' => 'A lesson cannot require itself.']);
                }
                if (! $locked->has($id) || $locked->get($id)->status === CurriculumStatus::Archived) {
                    throw ValidationException::withMessages(['prerequisite_lesson_ids' => 'Select existing lessons that are not archived.']);
                }
            }

            if ($source instanceof Lesson && $targetIds !== []) {
                $this->rejectCycle($source->id, $targetIds);
            }

            $links = $source->prerequisiteLessons();
            $current = $links->newPivotQuery()->lockForUpdate()->pluck('prerequisite_lesson_id')->all();
            $links->detach(array_diff($current, $targetIds));
            $links->attach(array_diff($targetIds, $current));
        }, 3);
    }

    private function rejectCycle(int $sourceId, array $targets): void
    {
        $edges = DB::table('lesson_prerequisite_lesson')->lockForUpdate()->get()->groupBy('lesson_id');
        $visited = [];

        while ($targets !== []) {
            $id = array_pop($targets);
            if ($id === $sourceId) {
                throw ValidationException::withMessages(['prerequisite_lesson_ids' => 'Prerequisite lessons cannot form a cycle.']);
            }
            if (isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;

            foreach ($edges->get($id, []) as $edge) {
                $targets[] = (int) $edge->prerequisite_lesson_id;
            }
        }
    }
}
