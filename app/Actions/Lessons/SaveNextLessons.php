<?php

namespace App\Actions\Lessons;

use App\Models\Lesson;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveNextLessons
{
    public function __construct(private SaveLessonResources $resources) {}

    public function handle(
        Lesson $lesson,
        ?array $targetIds,
        ?array $resourceIds = null,
        ?array $exerciseIds = null,
        ?array $examIds = null,
    ): void
    {
        if (in_array($lesson->id, array_map('intval', $targetIds ?? []), true)) {
            throw ValidationException::withMessages(['next_lesson_ids' => 'A lesson cannot link to itself.']);
        }

        DB::transaction(function () use ($lesson, $targetIds, $resourceIds, $exerciseIds, $examIds): void {
            $locked = Lesson::query()
                ->whereKey([$lesson->id, ...($targetIds ?? [])])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $source = $locked->get($lesson->id);
            abort_if($source === null, 404);

            foreach ($targetIds ?? [] as $targetId) {
                $target = $locked->get($targetId);
                if ($target === null || $target->status === CurriculumStatus::Archived) {
                    throw ValidationException::withMessages(['next_lesson_ids' => 'Select existing lessons that are not archived.']);
                }
            }

            if ($targetIds !== null) {
                $source->nextLessons()->sync($targetIds);
            }

            $this->saveAssessmentLinks($source->relatedExercises(), $exerciseIds, 'exercise_ids');
            $this->saveAssessmentLinks($source->relatedExams(), $examIds, 'exam_ids');

            if ($resourceIds !== null) {
                $this->resources->handle($source, $resourceIds);
            }
        }, 3);
    }

    private function saveAssessmentLinks(BelongsToMany $links, ?array $ids, string $field): void
    {
        if ($ids === null) {
            return;
        }

        $targets = $links->getRelated()->newQuery()->whereKey($ids)->orderBy('id')->lockForUpdate()->get(['id', 'status']);
        if ($targets->count() !== count($ids) || $targets->contains('status', CurriculumStatus::Archived)) {
            throw ValidationException::withMessages([$field => 'Select existing assessments that are not archived.']);
        }

        $links->sync($ids);
    }
}
