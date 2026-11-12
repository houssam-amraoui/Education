<?php

namespace Tests\Feature;

use App\Actions\Lessons\SavePrerequisiteLessons;
use App\Models\Category;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Queries\LearningRecommendations;
use App\Queries\PrerequisiteCompletion;
use App\Support\QuestionType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecommendationBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_prerequisite_links_are_replaced_atomically_and_reject_invalid_graph_changes(): void
    {
        $category = $this->category();
        $first = $this->lesson($category, 'First');
        $second = $this->lesson($category, 'Second');
        $third = $this->lesson($category, 'Third');
        $archived = $this->lesson($category, 'Archived', status: 'archived');
        $exercise = $this->exercise($category, 'Exercise');
        $exam = $this->exam($category);
        $save = app(SavePrerequisiteLessons::class);

        $save->handle($first, [$second->id]);
        $save->handle($second, [$third->id]);
        $save->handle($exercise, [$first->id]);
        $save->handle($exam, [$first->id, $second->id]);
        $save->handle($exam, [$second->id]);

        foreach ([
            [$third, [$first->id]],
            [$first, [$first->id]],
            [$first, [$archived->id]],
            [$exercise, [PHP_INT_MAX]],
            [$exam, [$second->id, (string) $second->id]],
        ] as [$content, $ids]) {
            try {
                $save->handle($content, $ids);
                $this->fail('Invalid prerequisite links were accepted.');
            } catch (ValidationException $exception) {
                $this->assertStringStartsWith('prerequisite_lesson_ids', array_key_first($exception->errors()));
            }
        }

        $this->assertSame([$second->id], $first->prerequisiteLessons()->pluck('lessons.id')->all());
        $this->assertSame([$third->id], $second->prerequisiteLessons()->pluck('lessons.id')->all());
        $this->assertFalse($third->prerequisiteLessons()->exists());
        $this->assertSame([$second->id], $exam->prerequisiteLessons()->pluck('lessons.id')->all());
        $this->assertSame([$first->id], $exercise->prerequisiteLessons()->pluck('lessons.id')->all());
        $save->handle($exercise, []);
        $this->assertFalse($exercise->prerequisiteLessons()->exists());
    }

    public function test_prerequisite_completion_is_owned_and_required_only_when_configured(): void
    {
        $student = $this->student();
        $other = $this->student();
        $category = $this->category();
        $exam = $this->exam($category);
        $lesson = $this->lesson($category, 'Preparation');
        $save = app(SavePrerequisiteLessons::class);
        $query = app(PrerequisiteCompletion::class);
        $this->assertFalse($query->forContent($student, $exam, required: true)['allows_access']);
        $save->handle($exam, [$lesson->id]);
        $this->progress($other, $lesson, completed: true);
        $this->progress($student, $lesson);

        $advisory = $query->forContent($student, $exam);
        $this->assertFalse($advisory['required']);
        $this->assertTrue($advisory['allows_access']);
        $this->assertFalse($advisory['lessons']->sole()->completed);
        $this->assertFalse($query->forContent($student, $exam, required: true)['allows_access']);

        LessonProgress::where('student_id', $student->id)->update(['completed_at' => now()]);
        $required = $query->forContent($student, $exam, required: true);
        $this->assertTrue($required['required']);
        $this->assertTrue($required['lessons']->sole()->completed);
        $this->assertTrue($required['allows_access']);

        $lesson->forceFill(['status' => 'archived'])->save();
        $hidden = $query->forContent($student, $exam, required: true);
        $this->assertTrue($hidden['lessons']->isEmpty());
        $this->assertFalse($hidden['allows_access']);
    }

    public function test_next_lessons_rank_configured_links_then_recent_views_and_filter_before_limiting(): void
    {
        $this->freezeSecond();
        $student = $this->student();
        $other = $this->student();
        $category = $this->category();
        $outside = $this->lesson($this->category(), 'Outside selection');
        $source = $this->lesson($category, 'Source');
        $older = $this->lesson($category, 'Older link', position: 1);
        $newer = $this->lesson($category, 'Recent link', position: 2);
        $fallback = $this->lesson($category, 'Fallback');
        $complete = $this->lesson($category, 'Completed');
        $hiddenCategory = $category->unit->categories()->create(['title' => 'Hidden']);
        $hiddenCategory->forceFill(['status' => 'archived'])->save();
        $hidden = $this->lesson($hiddenCategory, 'Hidden lesson');
        $source->nextLessons()->attach([$older->id, $newer->id, $complete->id, $hidden->id, $outside->id]);
        $this->progress($student, $complete, completed: true);
        $this->progress($student, $older);
        $this->travel(1)->minutes();
        $this->progress($student, $newer);
        $this->travel(1)->minutes();
        $this->progress($student, $fallback);
        $this->travel(1)->minutes();
        $this->progress($student, $source, completed: true);
        $this->progress($other, $older);

        $query = app(LearningRecommendations::class);
        $this->assertTrue($query->nextLessons($student)->isEmpty());
        $student->selectedLevels()->attach($category->unit->level_id);
        $this->assertSame([$newer->id, $older->id], $query->nextLessons($student, $source, 2)->modelKeys());
        $this->assertSame([$newer->id, $older->id, $fallback->id], $query->nextLessons($student)->modelKeys());
        $source->nextLessons()->detach();
        $this->assertSame([$fallback->id, $newer->id, $older->id], $query->nextLessons($student)->modelKeys());
    }

    public function test_exercise_suggestions_use_the_latest_scored_attempt_and_show_pending_exercises(): void
    {
        $this->freezeSecond();
        $student = $this->student();
        $other = $this->student();
        $category = $this->category();
        $student->selectedLevels()->attach($category->unit->level_id);
        $retry = $this->exercise($category, 'Retry');
        $pending = $this->exercise($category, 'Pending');
        $mastered = $this->exercise($category, 'Mastered');
        $draft = $this->exercise($category, 'Draft', 'draft');
        $outside = $this->exercise($this->category(), 'Outside selection');
        $category->unit->exercises()->forceCreate([
            'title' => 'No questions', 'instructions' => 'Practise.', 'status' => 'published',
        ]);

        foreach ([[$retry, 9], [$retry, 2], [$mastered, 1], [$mastered, 7], [$draft, 0], [$outside, 0]] as [$exercise, $points]) {
            $exercise->attempts()->forceCreate($this->score($student, $points));
        }
        $retry->attempts()->forceCreate($this->score($student, null));
        $retry->attempts()->forceCreate($this->score($student, 0, 0));
        $pending->attempts()->forceCreate($this->score($other, 10));

        $results = app(LearningRecommendations::class)->exercises($student, 2);
        $this->assertSame([$retry->id, $pending->id], $results->modelKeys());
        $this->assertSame(['retry', 'pending'], $results->pluck('recommendation_reason')->all());
    }

    public function test_exam_review_uses_the_saved_score_and_ranks_visible_prerequisites_before_unit_lessons(): void
    {
        $student = $this->student();
        $category = $this->category();
        $exam = $this->exam($category);
        $fallback = $this->lesson($category, 'Same unit');
        $linked = $this->lesson($this->category(), 'Configured review');
        $hidden = $this->lesson($category, 'Hidden prerequisite', status: 'draft');
        $this->lesson($this->category(), 'Unrelated');
        app(SavePrerequisiteLessons::class)->handle($exam, [$hidden->id, $linked->id]);

        $query = app(LearningRecommendations::class);
        $weak = $exam->attempts()->forceCreate($this->score($student, 6));
        $this->assertSame([$linked->id], $query->examReview($student, $weak, 1)->modelKeys());
        $this->assertSame([$linked->id, $fallback->id], $query->examReview($student, $weak)->modelKeys());
        $this->assertTrue($query->examReview($student, $exam->attempts()->forceCreate($this->score($student, 7)))->isEmpty());
        $expired = $exam->attempts()->forceCreate($this->score($student, null) + ['expired_at' => now()]);
        $this->assertTrue($query->examReview($student, $expired)->isEmpty());

        $this->expectException(ModelNotFoundException::class);
        $query->examReview($this->student(), $weak);
    }

    private function student(): User
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::where('key', Role::STUDENT)->sole());

        return $student;
    }

    private function category(): Category
    {
        $level = Level::forceCreate(['title' => 'Level', 'status' => 'published']);
        $unit = $level->units()->forceCreate(['title' => 'Unit', 'status' => 'published']);

        return $unit->categories()->create(['title' => 'Category']);
    }

    private function lesson(Category $category, string $title, ?int $position = null, string $status = 'published'): Lesson
    {
        return $category->lessons()->forceCreate(compact('title', 'position', 'status'));
    }

    private function exercise(Category $category, string $title, string $status = 'published'): Exercise
    {
        $exercise = $category->unit->exercises()->forceCreate(compact('title', 'status') + ['instructions' => 'Practise.']);
        $exercise->questions()->forceCreate([
            'type' => QuestionType::TrueFalse,
            'prompt' => 'Is this a test question?',
            'content' => [],
            'answer' => ['value' => true],
            'points' => 1,
            'position' => 1,
        ]);

        return $exercise;
    }

    private function exam(Category $category): Exam
    {
        return $category->unit->exams()->forceCreate(['title' => 'Exam', 'type' => Exam::INTERACTIVE, 'status' => 'published']);
    }

    private function progress(User $student, Lesson $lesson, bool $completed = false): void
    {
        $lesson->progress()->forceCreate([
            'student_id' => $student->id,
            'last_viewed_at' => $completed ? null : now(),
            'completed_at' => $completed ? now() : null,
        ]);
    }

    private function score(User $student, ?int $points, int $possible = 10): array
    {
        return [
            'student_id' => $student->id,
            'started_at' => now(),
            'submitted_at' => $points === null ? null : now(),
            'possible_points' => $possible,
            'awarded_points' => $points,
        ];
    }
}
