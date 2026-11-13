<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Level;
use App\Models\Resource;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalyticsBackendTest extends TestCase
{
    use RefreshDatabase;

    private const DATES = ['from' => '2026-11-01', 'through' => '2026-11-02'];

    public function test_live_signups_visits_and_authorized_usage_feed_the_reports(): void
    {
        $this->travelTo(now()->parse('2026-11-01 12:00:00', 'UTC'));
        $video = new Video(['title' => 'Video']);
        $video->forceFill(['provider' => 'youtube', 'provider_id' => 'dQw4w9WgXcQ', 'status' => 'published'])->save();
        $this->get(route('library.videos.show', $video))->assertOk()->assertSee('data-playback-url');
        $this->get(route('library.videos.show', $video))->assertOk();
        $this->assertDatabaseCount('analytics_events', 1);

        $this->postJson(route('account.register'), [
            'name' => 'New student', 'email' => 'new@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated();
        $student = User::where('email', 'new@example.test')->sole();
        $this->actingAs($student);
        $playback = route('library.videos.playback', $video);
        $this->post($playback)->assertNoContent();
        $this->post($playback)->assertNoContent();
        $video->forceFill(['status' => 'draft'])->save();
        $this->post($playback)->assertNotFound();

        $disk = Storage::fake(StoredFile::DISK);
        $file = StoredFile::create([
            'disk' => StoredFile::DISK, 'path' => 'resource.pdf', 'mime_type' => 'application/pdf', 'size' => 9,
        ]);
        $resource = Resource::create(['title' => 'PDF', 'type' => 'worksheet', 'access' => 'registered']);
        $resource->storedFile()->associate($file);
        $resource->forceFill(['status' => 'published'])->save();
        $this->get(route('library.resources.file', [$resource, 'inline']))->assertNotFound();
        $disk->put($file->path, "%PDF-1.7\n");
        $this->get(route('library.resources.file', [$resource, 'inline']))->assertOk();
        $this->get(route('library.resources.file', [$resource, 'attachment']))->assertOk();

        $report = $this->reportUrl();
        $this->getJson($report)->assertForbidden();
        $this->actingAs($this->user(Role::ADMINISTRATOR))->getJson($report)->assertOk()
            ->assertJsonPath('metrics.acquisition', ['visitors' => 1, 'registrations' => 1, 'active_students' => 1])
            ->assertJsonPath('metrics.engagement.resource_opens', 1)
            ->assertJsonPath('metrics.engagement.video_playbacks', 1);
    }

    public function test_period_aggregates_use_existing_learning_and_communication_records(): void
    {
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $level = Level::create(['title' => 'Level']);
        $unit = $level->units()->create(['title' => 'Unit']);
        $category = $unit->categories()->create(['title' => 'Category']);
        $lesson = $category->lessons()->create(['title' => 'Lesson']);
        foreach ([$level, $unit, $category, $lesson] as $record) {
            $record->forceFill(['status' => 'published'])->save();
        }

        $this->actingAs($student);
        $lessonUrl = route('curriculum.lesson', [$level, $unit, $category, $lesson]);
        $this->travelTo(now()->parse('2026-11-01 23:59:59', 'UTC'));
        $this->get($lessonUrl)->assertOk();
        $this->get($lessonUrl)->assertOk();
        $this->post(route('curriculum.lesson.complete', [$level, $unit, $category, $lesson]))->assertRedirect();
        $this->travelTo(now()->parse('2026-11-02 00:00:00', 'UTC'));
        $this->get($lessonUrl)->assertOk();
        $this->assertSame(2, AnalyticsEvent::where('event', AnalyticsEvent::LESSON_OPEN)->count());

        $exercise = DB::table('exercises')->insertGetId(['title' => 'Exercise', 'instructions' => 'Practice', 'unit_id' => $unit->id]);
        $exam = DB::table('exams')->insertGetId(['title' => 'Exam', 'type' => 'interactive', 'unit_id' => $unit->id]);
        $attempt = [
            'student_id' => $student->id, 'exercise_id' => $exercise, 'possible_points' => 10,
        ];
        DB::table('exercise_attempts')->insert($attempt + ['started_at' => '2026-10-31 23:59:59']);
        $submitted = DB::table('exercise_attempts')->insertGetId($attempt + [
            'started_at' => '2026-11-01 00:00:00', 'submitted_at' => '2026-11-02 00:00:00', 'awarded_points' => 4,
        ]);
        DB::table('exercise_attempts')->insert($attempt + ['started_at' => '2026-11-02 10:00:00', 'submitted_at' => '2026-11-03 00:00:00', 'awarded_points' => 10]);
        DB::table('exercise_attempt_items')->insert([
            'exercise_attempt_id' => $submitted, 'source_question_id' => 10, 'position' => 1,
            'type' => 'multiple_choice', 'prompt' => 'Prompt', 'content' => '[]', 'answer_key' => '[]',
            'points' => 10, 'correct' => false,
        ]);
        DB::table('exam_attempts')->insert([
            'student_id' => $student->id, 'exam_id' => $exam, 'possible_points' => 10,
            'started_at' => '2026-11-02 00:00:00', 'expired_at' => '2026-11-02 01:00:00',
        ]);
        $conversation = DB::table('conversations')->insertGetId([
            'student_id' => $student->id, 'subject' => 'Help', 'created_at' => '2026-11-01 23:59:59',
            'last_message_at' => '2026-11-02 00:00:10',
        ]);
        foreach ([
            [$student->id, false, '2026-11-01 23:59:59'],
            [$teacher->id, true, '2026-11-02 00:00:00'],
            [$teacher->id, true, '2026-11-02 00:00:10'],
        ] as [$sender, $fromTeacher, $created]) {
            DB::table('messages')->insert([
                'conversation_id' => $conversation, 'sender_id' => $sender, 'from_teacher' => $fromTeacher,
                'body' => 'Message', 'created_at' => $created,
            ]);
        }

        $this->actingAs($this->user(Role::ADMINISTRATOR))->getJson($this->reportUrl())->assertOk()
            ->assertJsonPath('metrics.engagement.lesson_opens', 2)
            ->assertJsonPath('metrics.engagement.lesson_completions', 1)
            ->assertJsonPath('metrics.engagement.exercise_starts', 2)
            ->assertJsonPath('metrics.engagement.exercise_repeat_attempts', 2)
            ->assertJsonPath('metrics.engagement.exercise_completions', 1)
            ->assertJsonPath('metrics.engagement.exam_attempts', 1)
            ->assertJsonPath('metrics.engagement.exam_completions', 0)
            ->assertJsonPath('metrics.engagement.learning_days', 2)
            ->assertJsonPath('metrics.engagement.students_with_consecutive_learning_days', 1)
            ->assertJsonPath('metrics.learning.exercises.completion_rate', 50)
            ->assertJsonPath('metrics.learning.exercises.average_score', 40)
            ->assertJsonPath('metrics.learning.progress.percentage', 100)
            ->assertJsonPath('metrics.learning.exercise_question_difficulty.0.incorrect_rate', 100)
            ->assertJsonPath('metrics.communication.ask_teacher_requests', 1)
            ->assertJsonPath('metrics.communication.messages', 3)
            ->assertJsonPath('metrics.communication.teacher_replies', 2)
            ->assertJsonPath('metrics.communication.average_first_reply_seconds', 1);
    }

    public function test_report_entry_checks_authority_and_dates_and_returns_unavailable_metrics(): void
    {
        $this->getJson($this->reportUrl())->assertUnauthorized();
        $this->actingAs($this->user(Role::TEACHER))->getJson($this->reportUrl())->assertForbidden();
        $this->actingAs($this->user(Role::ADMINISTRATOR));
        $this->getJson(route('admin.analytics.data'))->assertUnprocessable()->assertJsonValidationErrors(['from', 'through']);
        $this->getJson(route('admin.analytics.data', ['from' => '2026-11-02', 'through' => '2026-11-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('through');
        $this->getJson($this->reportUrl())->assertOk()
            ->assertJsonPath('metrics.business.available', false)
            ->assertJsonPath('metrics.business.paid_content_conversions', null)
            ->assertJsonPath('metrics.learning.exercises.completion_rate', null);
    }

    private function reportUrl(): string
    {
        return route('admin.analytics.data', self::DATES);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $role)->sole());

        return $user;
    }
}
