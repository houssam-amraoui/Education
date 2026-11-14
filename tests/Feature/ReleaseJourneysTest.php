<?php

namespace Tests\Feature;

use App\Models\AccessRule;
use App\Models\Conversation;
use App\Models\Exam;
use App\Models\Level;
use App\Models\PointCost;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ReleaseJourneysTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_and_keep_lesson_progress_and_exercise_corrections(): void
    {
        Storage::fake(StoredFile::DISK);
        $this->seed();
        $level = Level::where('title', '2nd Baccalaureate · Demo')->sole();
        $unit = $level->units()->where('title', 'Gifts of youth')->sole();
        $category = $unit->categories()->where('title', 'Grammar')->sole();
        $lesson = $category->lessons()->where('title', 'The passive voice')->sole();
        $exercise = $unit->exercises()->where('title', 'Passive voice practice')->sole();
        $lessonParameters = [$level, $unit, $category, $lesson];
        $lessonUrl = route('curriculum.lesson', $lessonParameters);
        $credentials = ['email' => 'release-learner@example.test', 'password' => 'sample-password'];
        $this->withHeaders(['Accept' => 'text/html']);

        $this->get(route('curriculum.paths'))->assertOk()->assertSee(route('curriculum.level', $level));
        $this->get(route('curriculum.level', $level))->assertOk()->assertSee(route('curriculum.level.units', $level));
        $this->get(route('curriculum.level.units', $level))->assertOk()->assertSee(route('curriculum.unit', [$level, $unit]));
        $this->get(route('curriculum.unit', [$level, $unit]))->assertOk()->assertSee(route('curriculum.category', [$level, $unit, $category]));
        $this->get(route('curriculum.category', [$level, $unit, $category]))->assertOk()->assertSee($lessonUrl);
        $this->get($lessonUrl)->assertOk()->assertDontSee('Mark as complete');
        $this->post(route('account.register'), $credentials + [
            'name' => 'Release Learner', 'password_confirmation' => $credentials['password'],
        ])->assertRedirect(route('account.login.show'));
        $this->post(route('account.login'), $credentials)->assertRedirect(route('account.home'));
        $this->put(route('account.learning-goals.update'), ['level_ids' => [$level->id]])->assertRedirect(route('account.home'));
        $this->get($lessonUrl)->assertOk()->assertSee('Mark as complete');
        $this->post(route('curriculum.lesson.complete', $lessonParameters))->assertRedirect($lessonUrl);

        $this->get(route('curriculum.exercises.instructions', $exercise))->assertOk()->assertSee('Start exercise');
        $started = $this->postJson(route('curriculum.exercises.attempts.store', $exercise))->assertCreated()->json();
        $attempt = [$exercise, $started['id']];
        $this->postJson(route('curriculum.exercises.attempts.submit', $attempt), [
            'answers' => array_combine(array_column($started['questions'], 'id'), [
                'b', ['verb' => 'was collected'], ['a'], 'The equipment will replaced',
            ]),
        ])->assertOk()->assertJsonPath('awarded_points', 0);

        $this->post(route('account.logout'))->assertRedirect(route('account.login.show'));
        $this->post(route('account.login'), $credentials)->assertRedirect(route('account.home'));
        $this->get(route('curriculum.exercises.attempts.show', $attempt))->assertOk()->assertSee('preserves the past tense');
        $this->get(route('account.progress.history'))->assertOk()->assertSee('Completed')->assertSee($lessonUrl)
            ->assertSee(route('curriculum.exercises.attempts.show', $attempt));
        $this->get(route('account.learning-goals.show'))->assertOk()->assertSee($level->title);
    }

    public function test_teacher_publishes_both_exam_types_and_student_can_use_them(): void
    {
        Storage::fake(StoredFile::DISK);
        $level = Level::forceCreate(['title' => 'Release path', 'status' => 'published']);
        $unit = $level->units()->forceCreate(['title' => 'Release unit', 'status' => 'published']);
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $store = route('admin.curriculum.exams.store');
        $common = ['unit_id' => $unit->id, 'access' => 'registered'];

        $this->actingAs($student)->postJson($store, $common)->assertForbidden();
        $this->actingAs($teacher);
        $document = Exam::findOrFail($this->postJson($store, $common + [
            'type' => Exam::DOCUMENT,
            'title' => 'Release exam paper',
            'exam_file' => UploadedFile::fake()->createWithContent('paper.pdf', "%PDF-1.4\n%%EOF\n"),
            'correction_file' => UploadedFile::fake()->createWithContent('correction.pdf', "%PDF-1.4\n%%EOF\n"),
        ])->assertCreated()->json('id'));
        $interactive = Exam::findOrFail($this->postJson($store, $common + [
            'type' => Exam::INTERACTIVE, 'title' => 'Release interactive exam',
            'instructions' => 'Answer the question.', 'duration_minutes' => null,
        ])->assertCreated()->json('id'));
        $this->postJson(route('admin.curriculum.exams.questions.store', $interactive), [
            'type' => 'true_false', 'prompt' => 'This is a practice exam.', 'points' => 2,
            'content' => [], 'answer' => ['value' => true], 'explanation' => 'This is a practice question.',
        ])->assertCreated();
        $catalogue = route('curriculum.exams.index');
        $this->actingAs($student)->get($catalogue)->assertOk()->assertDontSee($document->title)->assertDontSee($interactive->title);
        $this->actingAs($teacher);
        foreach ([$document, $interactive] as $exam) {
            $this->patchJson(route('admin.curriculum.exams.status', [$unit, $exam]), ['status' => 'published'])->assertNoContent();
        }

        $paper = route('curriculum.exams.file', [$document, 'paper', 'inline']);
        $correction = route('curriculum.exams.file', [$document, 'correction', 'attachment']);
        auth()->logout();
        $this->get($catalogue)->assertOk()->assertDontSee($document->title)->assertDontSee($interactive->title);
        $this->get($paper)->assertNotFound();
        $this->actingAs($student)->get($catalogue)->assertOk()->assertSee($document->title)->assertSee($interactive->title);
        $this->get(route('curriculum.exams.document', [$document, 'paper']))->assertOk()->assertSee($paper);
        $this->get($paper)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get($correction)->assertOk()->assertDownload('release-exam-paper-correction.pdf');

        $started = $this->postJson(route('curriculum.exams.attempts.store', $interactive))->assertCreated()->json();
        $attempt = [$interactive, $started['id']];
        $this->postJson(route('curriculum.exams.attempts.submit', $attempt), [
            'answers' => [$started['questions'][0]['id'] => true],
        ])->assertOk()->assertJsonPath('awarded_points', 2);
        $result = route('curriculum.exams.attempts.show', $attempt);
        $history = route('curriculum.exams.attempts.index', $interactive);
        $this->get($result, ['Accept' => 'text/html'])->assertOk()->assertSee('This is a practice question.');
        $this->get($history, ['Accept' => 'text/html'])->assertOk()->assertSee($result);
        $this->actingAs($this->user(Role::STUDENT))->getJson($result)->assertNotFound();
        $this->getJson($history)->assertOk()->assertJsonPath('total', 0);
    }

    public function test_lesson_question_reply_and_earned_points_unlock_restricted_practice_once(): void
    {
        Storage::fake(StoredFile::DISK);
        $this->seed();
        $level = Level::where('title', '2nd Baccalaureate · Demo')->sole();
        $unit = $level->units()->where('title', 'Gifts of youth')->sole();
        $category = $unit->categories()->where('title', 'Grammar')->sole();
        $lesson = $category->lessons()->where('title', 'The passive voice')->sole();
        $exercise = $unit->exercises()->where('title', 'Passive voice practice')->sole();
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        PointRule::create(['name' => 'Lesson credit', 'event' => 'lesson_completion', 'points' => 10, 'enabled' => true]);
        AccessRule::create(['target_type' => 'exercise', 'target_id' => $exercise->id, 'requirement' => 'points']);
        PointCost::create(['target_type' => 'exercise', 'target_id' => $exercise->id, 'points' => 6, 'enabled' => true]);

        $lessonParameters = [$level, $unit, $category, $lesson];
        $lessonUrl = route('curriculum.lesson', $lessonParameters);
        $compose = URL::signedRoute('account.messages.source', ['type' => 'lesson', 'source' => $lesson->id]);
        $destination = route('curriculum.exercises.instructions', $exercise);
        $unlock = route('access.unlock.show', ['exercise', $exercise->id]);
        $spend = route('access.unlock.store', ['exercise', $exercise->id]);
        $this->actingAs($student)->withHeaders(['Accept' => 'text/html']);
        $this->get($lessonUrl)->assertOk()->assertSee($compose);
        $this->get($compose)->assertOk()->assertSee('Attached learning context');
        $this->post($compose, ['subject' => 'Passive voice', 'message' => 'When should I use it?'])->assertStatus(303);
        $thread = Conversation::where('student_id', $student->id)->sole();
        $this->assertSame($lesson->id, $thread->source_id);

        $this->actingAs($teacher)->get(route('admin.messages.show', $thread))->assertOk()->assertSee($lessonUrl);
        $this->post(route('admin.messages.reply', $thread), ['reply' => 'Use it when the receiver matters most.'])->assertStatus(303);
        $this->actingAs($student)->get(route('account.messages.show', $thread))->assertOk()->assertSee('Use it when the receiver matters most.');
        $this->get($destination)->assertNotFound()->assertSee($unlock);
        $this->post($spend, ['points' => 6])->assertRedirect($unlock)->assertSessionHasErrors('points');
        $this->assertSame(0, PointTransaction::where('student_id', $student->id)->count());

        $complete = route('curriculum.lesson.complete', $lessonParameters);
        $this->post($complete)->assertRedirect($lessonUrl);
        $this->post($complete)->assertRedirect($lessonUrl);
        $this->get($unlock)->assertOk()->assertSee('Unlock for 6 points');
        $this->post($spend, ['points' => 6])->assertRedirect($unlock);
        $this->post($spend, ['points' => 6])->assertRedirect($unlock);
        $this->get($unlock)->assertOk()->assertSee($destination);
        $this->get($destination)->assertOk()->assertSee('Start exercise');
        $this->assertSame([8, 10, 3, -6], PointTransaction::where('student_id', $student->id)->orderBy('id')->pluck('amount')->all());
        $this->assertDatabaseHas('point_unlocks', ['student_id' => $student->id, 'point_cost_id' => PointCost::where('target_type', 'exercise')->where('target_id', $exercise->id)->sole()->id]);
        $this->assertDatabaseHas('entitlements', ['student_id' => $student->id, 'target_type' => 'exercise', 'target_id' => $exercise->id, 'source' => 'points']);
        $this->actingAs($this->user(Role::STUDENT))->get($destination)->assertNotFound();
    }

    private function user(string $role): User
    {
        return User::factory()->hasAttached(Role::where('key', $role)->sole())->create();
    }
}
