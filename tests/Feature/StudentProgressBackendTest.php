<?php

namespace Tests\Feature;

use App\Actions\Lessons\RecordLessonProgress;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExerciseAttempt;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Queries\ContinueLearning;
use App\Queries\ManagedStudentProgress;
use App\Queries\StudentProgress;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class StudentProgressBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_completion_is_explicit_and_preserves_the_first_completion(): void
    {
        $student = $this->user(Role::STUDENT);
        $lesson = $this->lesson('First lesson');
        $record = app(RecordLessonProgress::class);

        $this->freezeSecond();
        $record->view($student, $lesson);
        $this->assertNull(LessonProgress::sole()->completed_at);

        $this->travel(1)->minutes();
        $record->complete($student, $lesson);
        $completed = LessonProgress::sole()->completed_at;
        $this->assertSame(now()->toDateTimeString(), $completed->toDateTimeString());

        $this->travel(1)->minutes();
        $record->complete($student, $lesson);
        $record->view($student, $lesson);

        $progress = LessonProgress::sole();
        $this->assertEquals($completed, $progress->completed_at);
        $this->assertSame(now()->toDateTimeString(), $progress->last_viewed_at->toDateTimeString());
    }

    public function test_completion_and_dashboard_use_current_visibility_and_the_requested_level_scope(): void
    {
        $student = $this->user(Role::STUDENT);
        $first = $this->lesson('First lesson');
        $second = $this->lesson('Second lesson');
        $archived = $this->lesson('Archived lesson');
        $firstLevel = $first->category->unit->level_id;
        $secondLevel = $second->category->unit->level_id;
        $record = app(RecordLessonProgress::class);
        $record->view($student, $first);
        $record->complete($student, $first);
        $record->view($student, $second);
        $record->view($student, $archived);
        $record->complete($student, $archived);
        $archived->category->unit->forceFill(['status' => 'archived'])->save();

        $progress = app(StudentProgress::class);
        $this->assertSame(['completed' => 1, 'total' => 2, 'percentage' => 50], $progress->completion($student));
        $this->assertSame(['completed' => 1, 'total' => 1, 'percentage' => 100], $progress->completion($student, $firstLevel));
        $this->assertSame(['completed' => 0, 'total' => 0, 'percentage' => 0], $progress->completion($student, $archived->category->unit->level_id));

        $dashboard = app(ContinueLearning::class);
        $this->assertNull($dashboard->resume($student, []));
        $this->assertNull($dashboard->resume($student, [$firstLevel]));
        $this->assertSame($second->id, $dashboard->resume($student, [$firstLevel, $secondLevel])->lesson->id);
        $this->assertSame([$first->id], $dashboard->recentLessons($student, [$firstLevel])->pluck('lesson_id')->all());
        $this->assertTrue($dashboard->recentLessons($student, [$archived->category->unit->level_id])->isEmpty());
    }

    public function test_history_reads_owned_attempts_and_preserves_distinct_exam_outcomes(): void
    {
        $student = $this->user(Role::STUDENT);
        $other = $this->user(Role::STUDENT);
        $lesson = $this->lesson('First lesson');
        $unit = $lesson->category->unit;
        $exercise = $unit->exercises()->create(['title' => 'Practice', 'instructions' => 'Practice.']);
        $exam = $unit->exams()->create(['title' => 'Exam', 'type' => Exam::INTERACTIVE, 'access' => 'registered']);
        $exercise->forceFill(['status' => 'published'])->save();
        $exam->forceFill(['status' => 'published'])->save();

        $start = ['started_at' => '2026-01-01 00:00:00', 'possible_points' => 10];
        $completed = ['submitted_at' => '2026-01-01 00:01:00', 'awarded_points' => 7];
        ExerciseAttempt::forceCreate($start + $completed + ['student_id' => $student->id, 'exercise_id' => $exercise->id]);
        ExerciseAttempt::forceCreate($start + ['student_id' => $student->id, 'exercise_id' => $exercise->id]);
        ExerciseAttempt::forceCreate($start + $completed + ['student_id' => $other->id, 'exercise_id' => $exercise->id]);
        $owner = ['student_id' => $student->id, 'exam_id' => $exam->id];
        ExamAttempt::forceCreate($start + $completed + $owner);
        ExamAttempt::forceCreate($start + $owner + ['expired_at' => '2026-01-01 00:02:00']);
        ExamAttempt::forceCreate($start + $owner);

        $query = app(StudentProgress::class);
        $history = $query->history($student, $unit->level_id);
        $this->assertSame([
            'exam_expired', 'exam_completed', 'exercise_completed', 'exam_started', 'exam_started', 'exam_started',
        ], $history->pluck('event')->all());
        $this->assertSame([null, 7, 7, null, null, null], $history->pluck('awarded_points')->all());
        $this->assertSame(6, $query->history($student, perPage: 2)->total());

        $exam->forceFill(['available_until' => '2026-01-01 00:03:00'])->save();
        $this->travelTo(Carbon::parse('2026-01-01 00:03:00', 'UTC'));
        $this->assertSame(['exercise_completed'], $query->history($student)->pluck('event')->all());
    }

    public function test_managers_can_review_only_attempts_owned_by_the_selected_student(): void
    {
        $manager = $this->user(Role::ADMINISTRATOR);
        $student = $this->user(Role::STUDENT);
        $exercise = $this->lesson('First lesson')->category->unit->exercises()
            ->create(['title' => 'Practice', 'instructions' => 'Practice.']);
        $exercise->forceFill(['status' => 'published'])->save();
        $attempt = ExerciseAttempt::forceCreate([
            'exercise_id' => $exercise->id,
            'student_id' => $student->id,
            'started_at' => now(),
            'submitted_at' => now(),
            'possible_points' => 10,
            'awarded_points' => 7,
        ]);

        $query = app(ManagedStudentProgress::class);
        $this->assertSame(7, $query->review($manager, $student, $attempt)['awarded_points']);
        $this->assertSame(['exercise_completed'], $query->history($manager, $student)->pluck('event')->all());
        $this->expectException(AuthorizationException::class);
        $query->review($manager, $this->user(Role::STUDENT), $attempt);
    }

    public function test_manager_entry_alone_does_not_grant_student_progress_access(): void
    {
        $this->expectException(AuthorizationException::class);

        app(ManagedStudentProgress::class)->completion($this->user(Role::TEACHER), $this->user(Role::STUDENT));
    }

    public function test_lesson_progress_rejects_unpublished_lessons(): void
    {
        $lesson = $this->lesson('Hidden lesson');
        $lesson->forceFill(['status' => 'draft'])->save();

        $this->expectException(NotFoundHttpException::class);
        app(RecordLessonProgress::class)->complete($this->user(Role::STUDENT), $lesson);
    }

    private function lesson(string $title): Lesson
    {
        $level = Level::create(['title' => $title.' level']);
        $level->forceFill(['status' => 'published'])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => 'published'])->save();
        $lesson = $unit->categories()->create(['title' => 'Category'])->lessons()->create(['title' => $title]);
        $lesson->forceFill(['status' => 'published'])->save();

        return $lesson;
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $role)->firstOrFail());

        return $user;
    }
}
