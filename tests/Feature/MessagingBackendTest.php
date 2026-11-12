<?php

namespace Tests\Feature;

use App\Actions\Messaging\AppendConversationMessage;
use App\Actions\Messaging\CreateConversation;
use App\Models\Conversation;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Queries\Conversations;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MessagingBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_policies_distinguish_owners_other_students_and_managers(): void
    {
        $student = $this->participant(Role::STUDENT);
        $other = $this->participant(Role::STUDENT);
        $teacher = $this->participant(Role::TEACHER);
        $conversation = Conversation::forceCreate([
            'student_id' => $student->id,
            'subject' => 'Question',
            'last_message_at' => now(),
        ]);

        $this->assertTrue(Gate::forUser($student)->allows('followUp', $conversation));
        $this->assertSame(404, Gate::forUser($other)->inspect('view', $conversation)->status());
        $this->assertFalse(Gate::forUser($other)->allows('reply', $conversation));
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $conversation));
        $this->assertFalse(Gate::forUser($teacher)->allows('create', Conversation::class));
        $teacher->roles()->firstOrFail()->abilities()->where('ability', 'messages.manage')->delete();
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $conversation));
    }

    public function test_students_create_threads_with_authorized_source_snapshots_and_append_follow_ups(): void
    {
        $student = $this->participant(Role::STUDENT);
        $level = Level::forceCreate(['title' => 'Level', 'status' => 'published']);
        $unit = $level->units()->forceCreate(['title' => 'Unit', 'status' => 'published']);
        $category = $unit->categories()->create(['title' => 'Grammar']);
        $lesson = $category->lessons()->forceCreate(['title' => 'Passive voice', 'status' => 'published']);
        $create = app(CreateConversation::class);
        $input = ['subject' => 'A question', 'message' => 'When do I use it?'];
        $conversation = $create->handle($student, $input, 'lesson', $lesson->id)->refresh();

        $this->assertSame('lesson', $conversation->source_type);
        $this->assertSame($lesson->id, $conversation->source_id);
        $this->assertSame($student->id, $conversation->messages()->sole()->sender_id);

        $level->forceFill(['status' => 'archived'])->save();
        try {
            $create->handle($student, $input, 'lesson', $lesson->id);
            $this->fail('A lesson under a hidden level was accepted.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('conversations', 1);
        }

        $reply = app(AppendConversationMessage::class)->followUp($student, $conversation->id, ['reply' => 'An example?']);
        $this->assertFalse($reply->from_teacher);
        $this->assertSame('Passive voice', $conversation->fresh()->source_title);
        $this->assertDatabaseCount('messages', 2);
    }

    public function test_creation_rejects_submitted_recipients_and_source_overrides(): void
    {
        $student = $this->participant(Role::STUDENT);
        $other = $this->participant(Role::STUDENT);

        try {
            app(CreateConversation::class)->handle($student, [
                'subject' => 'Question', 'message' => 'Hello', 'recipient_id' => $other->id, 'source_id' => 99,
            ]);
            $this->fail('A submitted recipient and source were accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(['recipient_id', 'source_id'], array_keys($exception->errors()));
        }

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_teacher_inbox_filters_by_subject_or_student_and_replies_as_the_actor(): void
    {
        $student = $this->participant(Role::STUDENT);
        $student->forceFill(['name' => 'Amina'])->save();
        $teacher = $this->participant(Role::TEACHER);
        $create = app(CreateConversation::class);
        $thread = $create->handle($student, ['subject' => 'Passive voice', 'message' => 'A question']);
        $create->handle($student, ['subject' => 'Essay', 'message' => 'Another question']);
        $queries = app(Conversations::class);

        $this->assertSame(2, $queries->teacherInbox($teacher, 'Amina', perPage: 1)->total());
        $this->assertSame($thread->id, $queries->teacherInbox($teacher, 'passive')->sole()->id);
        $this->assertSame(0, $queries->teacherInbox($teacher, 'unmatched')->total());
        $reply = app(AppendConversationMessage::class)->reply($teacher, $thread->id, ['reply' => 'Here is an example.']);
        $this->assertTrue($reply->from_teacher);
        $this->assertSame($teacher->id, $reply->sender_id);
        $this->assertSame($reply->id, $queries->teacherInbox($teacher, 'passive')->sole()->latestMessage->id);
        $this->assertSame([$student->id, $teacher->id], $queries->teacherMessages($teacher, $thread->id)->pluck('sender_id')->all());

        $this->expectException(AuthorizationException::class);
        $queries->teacherThread($student, $thread->id);
    }

    public function test_student_queries_scope_threads_and_dashboard_replies_to_the_owner(): void
    {
        $student = $this->participant(Role::STUDENT);
        $other = $this->participant(Role::STUDENT);
        $teacher = $this->participant(Role::TEACHER);
        $create = app(CreateConversation::class);
        $append = app(AppendConversationMessage::class);
        $queries = app(Conversations::class);
        $input = ['subject' => 'A question', 'message' => 'Hello'];
        $owned = $create->handle($student, $input);
        $foreign = $create->handle($other, $input);
        $reply = $append->reply($teacher, $owned->id, ['reply' => 'Teacher answer']);
        $append->reply($teacher, $foreign->id, ['reply' => 'Private answer']);
        $followUp = $append->followUp($student, $owned->id, ['reply' => 'Thanks']);

        $inbox = $queries->studentInbox($student, perPage: 1);
        $this->assertSame(1, $inbox->total());
        $this->assertSame($owned->id, $inbox->sole()->id);
        $this->assertSame($followUp->id, $inbox->sole()->latestMessage->id);
        $this->assertSame($reply->id, $inbox->sole()->latestTeacherReply->id);
        $this->assertSame(3, $queries->studentMessages($student, $owned->id)->total());
        $this->assertSame([$reply->id], $queries->recentTeacherReplies($student)->modelKeys());
        $this->actingAs($student)->get(route('account.home'))->assertOk()
            ->assertViewHas('recentTeacherReplies', fn ($replies) => $replies->modelKeys() === [$reply->id]);

        $this->expectException(ModelNotFoundException::class);
        $queries->studentMessages($student, $foreign->id);
    }

    private function participant(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $role)->firstOrFail());

        return $user;
    }
}
