<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\DemonstrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemonstrationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_builds_a_repeatable_connected_catalogue_and_cohort(): void
    {
        Storage::fake('documents');
        $this->freezeTime();
        $this->seed();
        $tables = [
            'users', 'role_user', 'levels', 'units', 'categories', 'lessons', 'lesson_progress', 'questions',
            'exercises', 'exercise_attempts', 'exercise_attempt_items', 'exams', 'exam_attempts', 'exam_attempt_items',
            'topics', 'resources', 'stored_files', 'videos', 'conversations', 'messages', 'analytics_events',
            'point_rules', 'point_transactions', 'point_unlocks', 'achievements', 'student_achievements', 'access_rules', 'entitlements',
        ];
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
            $this->assertGreaterThan(0, $counts[$table], $table);
        }
        $student = User::where('email', 'demo.student@example.test')->sole();
        $this->assertTrue(Hash::check('password', $student->password));
        $this->assertSame([Role::STUDENT], $student->roles()->pluck('key')->all());
        $this->assertCount(2, $student->selectedLevels);
        $student->forceFill(['city' => 'Rabat'])->save();

        $this->seed();
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame('Rabat', $student->refresh()->city);
        $this->assertCount(6, Storage::disk('documents')->allFiles());
        $this->assertCount(2, Level::visibleToPublic()->get());
        $this->assertCount(7, DB::table('questions')->distinct()->pluck('type'));

        $lesson = Lesson::visibleToPublic()->where('title', 'The passive voice')->sole();
        $this->assertTrue($lesson->prerequisiteLessons()->sole()->nextLessons()->first()->is($lesson));
        $this->assertStringContainsString('في المبني للمجهول', $lesson->body);
        $topic = Topic::published()->where('title', 'Passive voice · Demo')->sole();
        $this->assertTrue($topic->exercises()->sole()->prerequisiteLessons()->sole()->is($lesson));

        $exercise = Exercise::where('title', 'Passive voice practice')->sole();
        $attempts = $exercise->attempts()->where('student_id', $student->id)->orderBy('id')->get();
        $this->assertSame([2, 7], $attempts->pluck('awarded_points')->all());
        $this->assertDatabaseHas('exam_attempts', ['submitted_at' => null, 'awarded_points' => null]);
        $this->assertSame(1, DB::table('exam_attempts')->whereNotNull('expired_at')->count());
        $resource = Resource::where('title', 'A request someone can act on')->sole();
        $this->assertTrue(Resource::visibleTo($student)->whereKey($resource)->exists());
        $this->assertFalse(Resource::visibleTo(null)->whereKey($resource)->exists());
    }

    public function test_seeded_screens_show_content_and_keep_keys_behind_authorized_review(): void
    {
        Storage::fake('documents');
        $this->seed();
        $this->get('/paths')->assertOk()->assertSee('Everyday English');
        $this->get('/videos')->assertOk()->assertSee('BBC Learning English');
        $this->get('/resources')->assertOk()->assertSee('Editing a school bulletin');
        $paper = Exam::where('type', Exam::DOCUMENT)->sole();
        $correction = "/exams/{$paper->id}/files/correction/inline";
        $this->get($correction)->assertNotFound();

        $student = User::where('email', 'demo.student@example.test')->sole();
        $this->actingAs($student)->get('/dashboard')->assertOk()->assertSee('Reported speech in a meeting summary');
        $this->get('/account/progress/activity')->assertOk()->assertSee('Passive voice practice');
        $this->get('/account/messages')->assertOk()->assertSee('Choosing was or were in a report');
        $this->get('/account/rewards')->assertOk()->assertSee('Steady learner');
        $this->get($correction)->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $exercise = Exercise::where('title', 'Confirm or reschedule?')->sole();
        $attempt = $exercise->attempts()->sole();
        $url = "/exercises/{$exercise->id}/attempts/{$attempt->id}";
        $this->get($url)->assertNotFound();
        $hanae = User::where('email', 'demo.hanae@example.test')->sole();
        $this->actingAs($hanae)->get($url)->assertOk()
            ->assertSee('Which message specifies a confirmed arrangement?')
            ->assertDontSee('The present continuous with an exact time describes an arrangement.');

        $teacher = User::where('email', 'demo.teacher@example.test')->sole();
        $this->actingAs($teacher)->get('/admin/inbox')->assertOk()->assertSee('Comment nuancer la conclusion ?');
        $admin = User::where('email', 'demo.admin@example.test')->sole();
        $this->actingAs($admin)->get('/admin/accounts/users')->assertOk()->assertSee('Nour Benjelloun');
        $this->get('/admin/analytics/learning')->assertOk()->assertSee('Exercise question #');
    }

    public function test_default_and_explicit_demo_seeds_skip_production(): void
    {
        $this->app->instance('env', 'production');

        try {
            $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
            $this->artisan('db:seed', ['--class' => DemonstrationSeeder::class, '--force' => true])->assertSuccessful();
        } finally {
            $this->app->instance('env', 'testing');
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('levels', 0);
    }
}
