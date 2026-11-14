<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Level;
use App\Models\Resource;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Models\Video;
use App\Queries\VisibleLessonDetail;
use App\Support\Ability;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_authoring_validates_and_scopes_writes_to_the_category(): void
    {
        $category = $this->category();
        $other = $this->category();
        $store = route('admin.curriculum.lessons.store', $category);
        $this->postJson($store, [])->assertUnauthorized();

        $viewer = Role::create(['name' => 'Admin area only']);
        $viewer->abilities()->create(['ability' => Ability::EnterAdministration->value]);
        $this->signInWithRole($viewer);
        $this->postJson($store, ['title' => 'Grammar'])->assertForbidden();

        $this->signInAsTeacher();
        $this->postJson($store, ['body' => []])->assertUnprocessable()->assertJsonValidationErrors(['title', 'body']);
        $id = $this->postJson($store, [
            'title' => 'Passive voice',
            'category_id' => $other->id,
            'position' => 1,
            'status' => 'published',
        ])->assertCreated()
            ->assertJsonPath('category_id', $category->id)
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('position', null)
            ->json('id');

        $this->putJson(route('admin.curriculum.lessons.update', [$other, $id]), ['title' => 'Wrong parent'])->assertNotFound();
        $this->putJson(route('admin.curriculum.lessons.update', [$category, $id]), [
            'title' => 'The passive voice',
            'body' => '<p>Example</p>',
        ])->assertOk();
        $this->assertDatabaseHas('lessons', [
            'id' => $id,
            'category_id' => $category->id,
            'title' => 'The passive voice',
            'body' => '<p>Example</p>',
        ]);
    }

    public function test_rich_content_is_sanitized_before_persistence(): void
    {
        $this->signInAsTeacher();
        $category = $this->category();
        $image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX9sAAAAASUVORK5CYII=';
        $html = <<<HTML
            <p lang="ar" dir="rtl" onclick="ignore"><strong>مثال</strong></p>
            <script>ignore</script><iframe src="https://example.com"></iframe>
            <a href="javascript:void(0)">Plain text</a>
            <a href="/paths">Paths</a>
            <img src="https://example.com/image.png">
            <img src="data:image/png;base64,bm90IGFuIGltYWdl">
            <img src="$image" alt="An example" onerror="ignore" srcset="https://example.com/image.png">
            HTML;
        $id = $this->postJson(route('admin.curriculum.lessons.store', $category), [
            'title' => 'Rich content',
            'body' => $html,
        ])->assertCreated()->json('id');

        $body = $category->lessons()->findOrFail($id)->body;
        $this->assertStringContainsString('<p lang="ar" dir="rtl"><strong>مثال</strong></p>', $body);
        $this->assertStringContainsString('Plain text', $body);
        $this->assertStringContainsString('<a href="/paths">Paths</a>', $body);
        $this->assertStringContainsString('<img src="'.$image.'" alt="An example" loading="lazy">', $body);
        foreach (['ignore', 'onclick', 'javascript:', 'iframe', 'example.com', 'bm90IGFuIGltYWdl', 'onerror', 'srcset'] as $discarded) {
            $this->assertStringNotContainsString($discarded, $body);
        }

        $this->putJson(route('admin.curriculum.lessons.update', [$category, $id]), [
            'title' => 'Rich content',
            'body' => '<a href="//example.com">Text</a><p>Updated</p>',
        ])->assertOk()->assertJsonPath('body', 'Text<p>Updated</p>');
    }

    public function test_authoring_links_existing_media_and_scopes_attachment_delivery(): void
    {
        $this->signInAsTeacher();
        $category = $this->category();
        $disk = Storage::fake(StoredFile::DISK);
        $disk->put('resources/notes.pdf', "%PDF-1.7\n");
        $file = StoredFile::create([
            'disk' => StoredFile::DISK, 'path' => 'resources/notes.pdf', 'mime_type' => 'application/pdf', 'size' => 9,
        ]);
        $resource = Resource::create(['title' => 'Notes', 'type' => 'pdf']);
        $resource->storedFile()->associate($file);
        $resource->forceFill(['status' => CurriculumStatus::Published])->save();
        $video = new Video(['title' => 'Example']);
        $video->forceFill(['provider' => 'youtube', 'provider_id' => 'dQw4w9WgXcQ'])->save();

        $store = route('admin.curriculum.lessons.store', $category);
        $ids = ['resource_ids' => [$resource->id], 'video_ids' => [$video->id]];
        $id = $this->postJson($store, ['title' => 'Source'] + $ids)->assertCreated()->json('id');
        $lesson = $category->lessons()->findOrFail($id);
        $update = route('admin.curriculum.lessons.update', [$category, $lesson]);
        $this->putJson($update, ['title' => 'Updated'] + $ids)->assertOk();
        $this->putJson($update, ['title' => 'Updated'])->assertOk();
        $this->putJson($update, ['title' => 'Rejected', 'resource_ids' => [99999], 'video_ids' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('resource_ids.0');
        $this->assertSame('Updated', $lesson->refresh()->title);
        $this->assertDatabaseCount('lesson_resource', 1);
        $this->assertSame([$video->id], $lesson->videos()->pluck('videos.id')->all());

        $fileUrl = route('library.lessons.resources.file', [$lesson, $resource, 'attachment']);
        $this->get($fileUrl)->assertNotFound();
        $lesson->forceFill(['status' => CurriculumStatus::Published])->save();
        $this->get($fileUrl)->assertOk()->assertDownload('notes.pdf');
        $this->putJson($update, ['title' => 'Updated', 'resource_ids' => [], 'video_ids' => []])->assertOk();
        $this->get($fileUrl)->assertNotFound();
        $this->assertFalse($lesson->videos()->exists());
        $disk->assertExists($file->path);
    }

    public function test_next_lessons_validate_targets_and_replace_links_atomically(): void
    {
        $this->signInAsTeacher();
        $category = $this->category();
        $lesson = $category->lessons()->create(['title' => 'Source']);
        $target = $this->category()->lessons()->create(['title' => 'Next lesson']);
        $archived = $category->lessons()->create(['title' => 'Archived']);
        $archived->forceFill(['status' => CurriculumStatus::Archived])->save();
        $route = route('admin.curriculum.lessons.next-lessons.update', [$category, $lesson]);

        $this->putJson($route, ['next_lesson_ids' => [$target->id]])->assertNoContent();
        $this->putJson($route, ['next_lesson_ids' => [$target->id]])->assertNoContent();
        foreach ([$lesson->id, $archived->id, 9999] as $invalidId) {
            $this->putJson($route, ['next_lesson_ids' => [$target->id, $invalidId]])
                ->assertUnprocessable()->assertJsonValidationErrors('next_lesson_ids');
        }
        $this->putJson($route, ['next_lesson_ids' => [$target->id, $target->id]])->assertUnprocessable();
        $this->assertSame([$target->id], $lesson->nextLessons()->pluck('lessons.id')->all());
        $this->putJson($route, ['next_lesson_ids' => []])->assertNoContent();
        $this->assertFalse($lesson->nextLessons()->exists());
    }

    public function test_related_assessments_are_validated_and_saved_with_next_lessons(): void
    {
        $category = $this->category();
        $other = $this->category();
        $lesson = $category->lessons()->create(['title' => 'Source']);
        $next = $other->lessons()->create(['title' => 'Next']);
        $exercise = Exercise::create(['unit_id' => $other->unit_id, 'title' => 'Practice', 'instructions' => 'Answer.']);
        $exam = Exam::create([
            'unit_id' => $category->unit_id, 'title' => 'Assessment', 'type' => Exam::INTERACTIVE, 'instructions' => 'Answer.',
        ]);
        $archived = $exam->replicate();
        $archived->forceFill(['status' => CurriculumStatus::Archived])->save();
        $save = route('admin.curriculum.lessons.next-lessons.update', [$category, $lesson]);
        $links = ['next_lesson_ids' => [$next->id], 'exercise_ids' => [(string) $exercise->id], 'exam_ids' => [$exam->id]];

        $this->putJson($save, $links)->assertUnauthorized();
        $this->signInAsTeacher();
        $this->putJson(route('admin.curriculum.lessons.next-lessons.update', [$other, $lesson]), $links)->assertNotFound();
        $this->putJson($save, [])->assertUnprocessable()->assertJsonValidationErrors('next_lesson_ids');
        $this->putJson($save, $links)->assertNoContent();
        $this->putJson($save, $links)->assertNoContent();
        $this->putJson($save, ['exercise_ids' => [$exercise->id, $exercise->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('exercise_ids.1');
        $this->putJson($save, ['exercise_ids' => [99999]])
            ->assertUnprocessable()->assertJsonValidationErrors('exercise_ids');
        $this->putJson($save, ['exam_ids' => [$archived->id], 'exercise_ids' => [], 'next_lesson_ids' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('exam_ids');
        $this->assertSame([$next->id], $lesson->nextLessons()->pluck('lessons.id')->all());
        $this->assertSame([$exercise->id], $lesson->relatedExercises()->pluck('exercises.id')->all());
        $this->assertSame([$exam->id], $lesson->relatedExams()->pluck('exams.id')->all());

        $this->putJson($save, ['exercise_ids' => [], 'exam_ids' => []])->assertNoContent();
        $this->assertFalse($lesson->relatedExercises()->exists());
        $this->assertFalse($lesson->relatedExams()->exists());
        $this->assertTrue($lesson->nextLessons()->exists());
    }

    public function test_visible_lesson_detail_only_loads_accessible_related_assessments(): void
    {
        $category = $this->category();
        $lesson = $category->lessons()->create(['title' => 'Source']);
        $lesson->forceFill(['status' => CurriculumStatus::Published])->save();
        $exercise = Exercise::create(['unit_id' => $category->unit_id, 'title' => 'Practice', 'instructions' => 'Answer.']);
        $exam = Exam::create([
            'unit_id' => $category->unit_id, 'title' => 'Assessment', 'type' => Exam::INTERACTIVE, 'access' => 'registered',
            'instructions' => 'Answer.',
        ]);
        $exam->forceFill(['status' => CurriculumStatus::Published])->save();
        $lesson->relatedExercises()->attach($exercise);
        $lesson->relatedExams()->attach($exam);
        $detail = app(VisibleLessonDetail::class);
        $guest = $detail->findOrFail($category, $lesson->id);
        $this->assertTrue($guest->relatedExercises->isEmpty());
        $this->assertTrue($guest->relatedExams->isEmpty());

        $exercise->forceFill(['status' => CurriculumStatus::Published])->save();
        $this->signInWithRole(Role::where('key', Role::STUDENT)->sole());
        $student = $detail->findOrFail($category, $lesson->id, auth()->user());
        $this->assertSame([$exercise->id], $student->relatedExercises->modelKeys());
        $this->assertSame([$exam->id], $student->relatedExams->modelKeys());
    }

    public function test_lifecycle_and_order_respect_category_scope(): void
    {
        $this->signInAsTeacher();
        $category = $this->category();
        $other = $this->category();
        $first = $category->lessons()->create(['title' => 'First']);
        $second = $category->lessons()->create(['title' => 'Second']);
        $unrelated = $other->lessons()->create(['title' => 'Unrelated']);
        $order = route('admin.curriculum.lessons.reorder', [$category, $second]);
        $this->patchJson(route('admin.curriculum.lessons.reorder', [$other, $second]), ['position' => 1])->assertNotFound();
        $this->patchJson($order, ['position' => 3])->assertUnprocessable()->assertJsonValidationErrors('position');
        $this->patchJson($order, ['position' => 1])->assertNoContent();
        $this->assertSame([$second->id, $first->id], $category->lessons()->ordered()->pluck('id')->all());
        $this->assertNull($unrelated->refresh()->position);

        $status = route('admin.curriculum.lessons.status', [$category, $first]);
        $this->patchJson($status, ['status' => 'published'])->assertNoContent();
        $this->assertSame(CurriculumStatus::Published, $first->refresh()->status);
        $this->patchJson($status, ['status' => 'draft'])->assertNoContent();
        $this->assertSame(CurriculumStatus::Draft, $first->refresh()->status);
        $this->patchJson($status, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($status, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($status, ['status' => 'published'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame(CurriculumStatus::Archived, $first->refresh()->status);
    }

    public function test_detail_query_requires_the_lesson_and_all_ancestors_to_be_published(): void
    {
        $category = $this->category();
        $lesson = $category->lessons()->create(['title' => 'Passive voice']);
        $detail = app(VisibleLessonDetail::class);
        $this->assertHidden($detail, $category, $lesson->id);
        $lesson->forceFill(['status' => CurriculumStatus::Published])->save();
        $this->assertSame($lesson->id, $detail->findOrFail($category, $lesson->id)->id);
        $this->assertHidden($detail, $this->category(), $lesson->id);

        foreach ([$category, $category->unit, $category->unit->level] as $ancestor) {
            $ancestor->forceFill(['status' => CurriculumStatus::Archived])->save();
            $this->assertHidden($detail, $category, $lesson->id);
            $ancestor->forceFill(['status' => CurriculumStatus::Published])->save();
        }
    }

    private function assertHidden(VisibleLessonDetail $detail, Category $category, int $lessonId): void
    {
        try {
            $detail->findOrFail($category, $lessonId);
            $this->fail('The lesson should be hidden.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame([$lessonId], $exception->getIds());
        }
    }

    private function category(): Category
    {
        $level = Level::create(['title' => 'Level']);
        $level->forceFill(['status' => CurriculumStatus::Published])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => CurriculumStatus::Published])->save();

        return $unit->categories()->create(['title' => 'Grammar']);
    }

    private function signInAsTeacher(): void
    {
        $this->signInWithRole(Role::where('key', Role::TEACHER)->sole());
    }

    private function signInWithRole(Role $role): void
    {
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user);
    }
}
