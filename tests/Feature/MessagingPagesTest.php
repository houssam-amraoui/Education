<?php

namespace Tests\Feature;

use App\Actions\Messaging\CreateConversation;
use App\Models\Conversation;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MessagingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_compose_replies_and_dashboard_use_persisted_conversations(): void
    {
        $student = $this->participant('Samira', Role::STUDENT);
        $teacher = $this->participant('Teacher', Role::TEACHER);
        $level = Level::forceCreate(['title' => 'Level', 'status' => 'published']);
        $unit = $level->units()->forceCreate(['title' => 'Unit', 'status' => 'published']);
        $category = $unit->categories()->create(['title' => 'Grammar']);
        $lesson = $category->lessons()->forceCreate(['title' => 'Passive voice', 'status' => 'published']);
        $lessonUrl = route('curriculum.lesson', [$level, $unit, $category, $lesson]);
        $compose = URL::signedRoute('account.messages.source', ['type' => 'lesson', 'source' => $lesson->id]);
        $this->withHeaders(['Accept' => 'text/html']);

        $this->get($lessonUrl)->assertOk()->assertSee($compose);
        $this->get($compose)->assertRedirect(route('account.login.show'));
        $this->actingAs($student)->get(route('account.messages.index'))->assertOk()->assertSee('No conversations yet');
        $this->get($compose)->assertOk()->assertSee('Attached learning context')->assertSee('Question about Passive voice');
        $this->post($compose, ['subject' => 'My lesson question'])->assertRedirect($compose)->assertSessionHasErrors('message');
        $this->get($compose)->assertOk()->assertSee('aria-invalid="true"', false)->assertSee('My lesson question');
        $this->post($compose, ['subject' => 'My lesson question', 'message' => 'How is it used?'])->assertStatus(303);

        $thread = Conversation::sole();
        $this->assertSame($lesson->id, $thread->source_id);
        $studentThread = route('account.messages.show', $thread);
        $teacherThread = route('admin.messages.show', $thread);
        $this->get($studentThread)->assertOk()->assertSee('How is it used?')->assertSee($lessonUrl);
        $this->post($studentThread, ['reply' => 'Could you give an example?'])->assertStatus(303);
        $this->get(route('account.home'))->assertOk()->assertSee('No teacher replies yet.');

        $this->actingAs($teacher)->get(route('admin.messages.index', ['search' => 'Samira']))
            ->assertOk()->assertSee('My lesson question')->assertSee($teacherThread);
        $this->get(route('admin.messages.index', ['search' => 'unmatched']))->assertOk()->assertSee('No matching conversations');
        $this->get($teacherThread)->assertOk()->assertSee('Could you give an example?')->assertSee($lessonUrl);
        $this->post($teacherThread, ['reply' => '   '])->assertRedirect($teacherThread)->assertSessionHasErrors('reply');
        $this->post($teacherThread, ['reply' => 'Here is a saved example.'])->assertStatus(303);
        $this->assertSame($teacher->id, $thread->latestTeacherReply()->firstOrFail()->sender_id);

        $this->actingAs($student)->get(route('account.messages.index', ['search' => 'lesson']))
            ->assertOk()->assertSee('Here is a saved example.')->assertSee($studentThread);
        $this->get(route('account.home'))->assertOk()->assertSee('Here is a saved example.')->assertSee($studentThread);
        $this->get($studentThread)->assertOk()->assertSee('Here is a saved example.');

        $level->forceFill(['status' => 'archived'])->save();
        $this->get($compose)->assertNotFound();
        $this->post($compose, ['subject' => 'Another question', 'message' => 'Hello'])->assertNotFound();
        $this->actingAs($teacher)->get($teacherThread)
            ->assertOk()->assertSee('Passive voice')->assertSee('no longer available')->assertDontSee($lessonUrl);
    }

    public function test_live_routes_reject_cross_student_access_and_tampered_context(): void
    {
        $student = $this->participant('Student', Role::STUDENT);
        $other = $this->participant('Other student', Role::STUDENT);
        $thread = app(CreateConversation::class)->handle($other, ['subject' => 'Private subject', 'message' => 'Private body']);
        $this->actingAs($student)->withHeaders(['Accept' => 'text/html']);

        $this->get(route('account.messages.index'))->assertOk()->assertDontSee('Private subject');
        $this->get(route('account.messages.show', $thread))->assertNotFound();
        $this->post(route('account.messages.reply', $thread), ['reply' => ''])->assertNotFound();
        $this->get(route('admin.messages.index'))->assertForbidden();
        $this->post(route('admin.messages.reply', $thread), ['reply' => 'Unauthorized'])->assertForbidden();
        $this->post(route('account.messages.store'), [
            'subject' => 'Question', 'message' => 'Hello', 'recipient_id' => $other->id,
        ])->assertSessionHasErrors('recipient_id');
        $this->get(route('account.messages.source', ['type' => 'lesson', 'source' => 1]))->assertForbidden();
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('messages', 1);
    }

    private function participant(string $name, string $role): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->roles()->attach(Role::where('key', $role)->firstOrFail());

        return $user;
    }
}
