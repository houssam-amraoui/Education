<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Curriculum\ChangeCurriculumStatus;
use App\Actions\Curriculum\ReorderCurriculum;
use App\Actions\Lessons\SaveLesson;
use App\Actions\Lessons\SaveNextLessons;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCurriculumStatusRequest;
use App\Http\Requests\Admin\ReorderCurriculumRequest;
use App\Http\Requests\Admin\SaveLessonRequest;
use App\Http\Requests\Admin\SaveNextLessonsRequest;
use App\Models\Category;
use App\Models\Lesson;
use App\Support\AccountPages;
use App\Support\CurriculumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class LessonController extends Controller
{
    public function store(SaveLessonRequest $request, Category $category, SaveLesson $save): JsonResponse|RedirectResponse
    {
        $lesson = $save->handle($category, new Lesson, $request->safe()->only(['title', 'body']), $request->validated('video_ids'));

        return AccountPages::html($request)
            ? $this->backToList($category, 'Lesson created.')
            : response()->json($lesson, 201);
    }

    public function update(SaveLessonRequest $request, Category $category, Lesson $lesson, SaveLesson $save): JsonResponse|RedirectResponse
    {
        $lesson = $save->handle($category, $lesson, $request->safe()->only(['title', 'body']), $request->validated('video_ids'));

        return AccountPages::html($request)
            ? $this->backToList($category, 'Lesson updated.')
            : response()->json($lesson);
    }

    public function nextLessons(SaveNextLessonsRequest $request, Category $category, Lesson $lesson, SaveNextLessons $save): Response|RedirectResponse
    {
        $save->handle($lesson, $request->validated('next_lesson_ids'), $request->validated('resource_ids'));

        return AccountPages::html($request)
            ? redirect()->route('admin.curriculum.lessons.relationships', [$category, $lesson], status: 303)->with('status', 'Relationships saved.')
            : response()->noContent();
    }

    public function status(ChangeCurriculumStatusRequest $request, Category $category, Lesson $lesson, ChangeCurriculumStatus $change): Response|RedirectResponse
    {
        $change->handle(
            Lesson::whereBelongsTo($category),
            $lesson->id,
            $request->enum('status', CurriculumStatus::class),
        );

        if (! AccountPages::html($request)) {
            return response()->noContent();
        }

        return $request->input('return_to') === 'editor'
            ? redirect()->route('admin.curriculum.lessons.edit', [$category, $lesson], status: 303)->with('status', 'Lesson state saved.')
            : $this->backToList($category, 'Lesson state saved.');
    }

    public function reorder(ReorderCurriculumRequest $request, Category $category, Lesson $lesson, ReorderCurriculum $reorder): Response|RedirectResponse
    {
        $reorder->handle(Lesson::whereBelongsTo($category), $lesson->id, $request->integer('position'));

        return AccountPages::html($request)
            ? $this->backToList($category, 'Order updated.')
            : response()->noContent();
    }

    private function backToList(Category $category, string $status): RedirectResponse
    {
        return redirect()->route('admin.curriculum.lessons.index', ['category_id' => $category->id], status: 303)->with('status', $status);
    }
}
