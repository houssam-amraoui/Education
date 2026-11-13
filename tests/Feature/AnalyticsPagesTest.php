<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_reports_preserve_the_period_and_link_to_authorized_records(): void
    {
        $student = $this->user(Role::STUDENT);
        $admin = $this->user(Role::ADMINISTRATOR);
        $this->travelTo(now()->parse('2026-11-02 12:00:00', 'UTC'));
        DB::table('analytics_events')->insert([
            'event' => AnalyticsEvent::VIDEO_PLAYBACK, 'actor_key' => hash('sha256', 'student'),
            'student_id' => $student->id, 'target_id' => 1, 'event_day' => '2026-11-02',
            'occurred_at' => now(),
        ]);
        $conversation = DB::table('conversations')->insertGetId([
            'student_id' => $student->id, 'subject' => 'Question', 'created_at' => now(), 'last_message_at' => now(),
        ]);
        DB::table('messages')->insert([
            'conversation_id' => $conversation, 'sender_id' => $student->id, 'from_teacher' => false,
            'body' => 'Question', 'created_at' => now(),
        ]);

        $dates = ['from' => '2026-11-02', 'through' => '2026-11-02'];
        $page = fn (string $report) => route('admin.analytics.show', ['report' => $report] + $dates);
        $this->get($page('learning'))->assertRedirect(route('account.login.show'));
        $this->actingAs($student)->get($page('learning'))->assertForbidden();
        $this->actingAs($this->user(Role::TEACHER))->get($page('communication'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.overview'))->assertSee(route('admin.analytics.show'));
        $this->get($page('learning'))->assertOk()->assertSee($page('communication'))
            ->assertSee(route('admin.accounts.users.progress', $student))->assertDontSee('Sample metric');
        $this->get($page('communication'))->assertOk()->assertSee('2026-11-02 1')
            ->assertSee(route('admin.messages.show', $conversation))->assertDontSee('Reply to Amina');
        $this->get($page('engagement'))->assertOk()->assertSee('Video playbacks · 1');
        $this->get($page('acquisition'))->assertOk()->assertSee('2026-11-02 1');
        $this->get(route('admin.analytics.show'))->assertOk()->assertSee('Active students');
        $this->get($page('business'))->assertOk()
            ->assertSee('Business metrics aren’t available yet.')
            ->assertSee('Teacher-service lead capture is not configured.');

        $admin->roles()->firstOrFail()->abilities()->whereIn('ability', ['users.manage', 'messages.manage'])->delete();
        $this->get($page('learning'))->assertOk()->assertDontSee('Inspect student activity');
        $this->get($page('communication'))->assertOk()->assertDontSee('Open conversation');
    }

    public function test_period_errors_return_to_the_report_and_empty_ranges_have_an_explicit_state(): void
    {
        $this->actingAs($this->user(Role::ADMINISTRATOR));
        $url = route('admin.analytics.show', 'learning');
        $this->get($url.'?from=2026-11-02&through=2026-11-01')
            ->assertRedirect($url)->assertSessionHasErrors('through');
        $this->get($url)->assertOk()->assertSee('Enter a valid reporting period')->assertSee('aria-invalid="true"', false);
        $this->get($url.'?from=2026-11-01&through=2026-11-02')->assertOk()
            ->assertSee('No student activity')->assertSee('No graded questions in this period.');
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $role)->sole());

        return $user;
    }
}
