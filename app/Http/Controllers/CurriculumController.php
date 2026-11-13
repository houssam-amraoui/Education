<?php

namespace App\Http\Controllers;

use App\Actions\Lessons\RecordLessonProgress;
use App\Http\Requests\CurriculumFiltersRequest;
use App\Models\Category;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Level;
use App\Models\Unit;
use App\Queries\LearningRecommendations;
use App\Queries\PrerequisiteCompletion;
use App\Queries\VisibleLessonDetail;
use App\Support\AccountPages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    public function home(): View
    {
        return AccountPages::view('G01', 'home', [
            'levels' => $this->levelsQuery()->limit(3)->get(),
        ], 'curriculum.home');
    }

    public function paths(CurriculumFiltersRequest $request): View
    {
        return $this->listing('G02', $request, $this->levelsQuery(), ['G01']);
    }

    public function level(string $level): View
    {
        $record = Level::visibleTo(request()->user())->withCount(['units' => fn (Builder $units) => $units->visibleTo(request()->user())])->findOrFail($level);

        return AccountPages::view('G03', 'level', [
            'level' => $record,
            'title' => $record->title,
            'crumbs' => ['G01', 'G02'],
        ], 'curriculum.catalogue');
    }

    public function units(CurriculumFiltersRequest $request, ?string $level = null): View
    {
        $record = $level === null ? null : Level::visibleTo(request()->user())->findOrFail($level);
        $query = Unit::visibleTo(request()->user())->with('level')->withCount(['categories' => fn (Builder $categories) => $categories->visibleTo(request()->user())]);

        if ($record) {
            $query->whereBelongsTo($record);
        } else {
            $query->orderBy('level_id');
        }

        return $this->listing('G04', $request, $query->ordered(), $record ? ['G01', 'G02', 'G03'] : ['G01', 'G02'], $record);
    }

    public function unit(CurriculumFiltersRequest $request, string $level, string $unit): View
    {
        $record = Level::visibleTo(request()->user())->findOrFail($level);
        $unitRecord = $record->units()->visibleTo(request()->user())->findOrFail($unit);

        return $this->listing(
            'G05', $request, $unitRecord->categories()->visibleTo(request()->user())->ordered()->getQuery(),
            ['G01', 'G02', 'G03', 'G04'], $record, $unitRecord,
        );
    }

    public function category(CurriculumFiltersRequest $request, string $level, string $unit, string $category): View
    {
        $record = Level::visibleTo(request()->user())->findOrFail($level);
        $unitRecord = $record->units()->visibleTo(request()->user())->findOrFail($unit);
        $categoryRecord = $unitRecord->categories()->visibleTo(request()->user())->findOrFail($category);

        return $this->listing(
            'G06', $request, $categoryRecord->lessons()->visibleTo(request()->user())->ordered()->getQuery(),
            ['G01', 'G02', 'G03', 'G04', 'G05'], $record, $unitRecord, $categoryRecord,
        );
    }

    public function lesson(
        string $level,
        string $unit,
        string $category,
        string $lesson,
        VisibleLessonDetail $detail,
        RecordLessonProgress $progress,
        PrerequisiteCompletion $preparation,
        LearningRecommendations $recommendations,
    ): View {
        $record = Level::visibleTo(request()->user())->findOrFail($level);
        $unitRecord = $record->units()->visibleTo(request()->user())->findOrFail($unit);
        $categoryRecord = $unitRecord->categories()->visibleTo(request()->user())->findOrFail($category);
        $lessonRecord = $detail->findOrFail($categoryRecord, (int) $lesson, request()->user());
        $student = request()->user();
        $canTrack = $student && Gate::allows('manageLearningProfile', $student);
        if ($canTrack && request()->isMethod('GET')) {
            $progress->view($student, $lessonRecord);
        }
        $lessonRecord->load([
            'resources' => fn ($resources) => $resources->visibleTo(request()->user())->matching([]),
            'videos' => fn ($videos) => $videos->visibleTo(request()->user())->ordered(),
        ]);

        return AccountPages::view('G07', 'lesson', [
            'title' => $lessonRecord->title,
            'crumbs' => ['G01', 'G02', 'G03', 'G04', 'G05', 'G06'],
            'links' => $this->context($record, $unitRecord, $categoryRecord),
            'lesson' => $lessonRecord,
            'prerequisites' => $preparation->guidance($student, $lessonRecord),
            'nextLessons' => $canTrack ? $recommendations->nextLessons($student, $lessonRecord) : $lessonRecord->nextLessons,
            'canTrack' => $canTrack,
            'completed' => $canTrack && LessonProgress::where('student_id', $student->id)
                ->where('lesson_id', $lessonRecord->id)->whereNotNull('completed_at')->exists(),
        ], 'curriculum.lesson');
    }

    public function completeLesson(string $level, string $unit, string $category, string $lesson, RecordLessonProgress $progress): RedirectResponse
    {
        $record = Lesson::visibleTo(request()->user())->whereRelation('category', 'id', $category)
            ->whereRelation('category.unit', 'id', $unit)
            ->whereRelation('category.unit.level', 'id', $level)->findOrFail($lesson);
        $progress->complete(request()->user(), $record);

        return redirect()->route('curriculum.lesson', [$level, $unit, $category, $lesson], 303)
            ->with('status', 'Lesson marked as complete.');
    }

    private function levelsQuery(): Builder
    {
        return Level::visibleTo(request()->user())->withCount(['units' => fn (Builder $units) => $units->visibleTo(request()->user())])->ordered();
    }

    private function listing(string $id, CurriculumFiltersRequest $request, Builder $query, array $crumbs, ?Level $level = null, ?Unit $unit = null, ?Category $category = null): View
    {
        $search = $request->validated('search');
        if ($search !== null) {
            $query->where(fn (Builder $items) => $items->whereLike('title', '%'.$search.'%')
                ->when($category === null, fn (Builder $items) => $items->orWhereLike('description', '%'.$search.'%')));
        }

        return AccountPages::view($id, 'list', [
            'records' => $query->paginate(12)->appends($request->validated()),
            'search' => $search,
            'level' => $level,
            'unit' => $unit,
            'category' => $category,
            'title' => $category ? $category->title.' lessons' : $unit?->title,
            'crumbs' => $crumbs,
            'links' => $this->context($level, $unit, $category),
        ], 'curriculum.catalogue');
    }

    private function context(?Level $level, ?Unit $unit, ?Category $category = null): array
    {
        $links = [];
        if ($level) {
            $links['G03'] = ['title' => $level->title, 'url' => route('curriculum.level', $level)];
            $links['G04'] = ['title' => 'Units', 'url' => route('curriculum.level.units', $level)];
        }
        if ($unit) {
            $links['G05'] = ['title' => $unit->title, 'url' => route('curriculum.unit', [$level, $unit])];
        }
        if ($category) {
            $links['G06'] = ['title' => $category->title, 'url' => route('curriculum.category', [$level, $unit, $category])];
        }

        return $links;
    }
}
