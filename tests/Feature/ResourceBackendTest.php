<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Resource;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\Ability;
use App\Support\CurriculumStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResourceBackendTest extends TestCase
{
    use RefreshDatabase;

    private array $uploadFixtures = [];

    public function test_authorized_managers_can_save_and_filter_resource_metadata(): void
    {
        $store = route('admin.library.resources.store');
        $list = route('admin.library.resources.index');
        $this->postJson($store)->assertUnauthorized();
        $viewer = Role::create(['name' => 'Admin area only']);
        $viewer->abilities()->create(['ability' => Ability::EnterAdministration->value]);
        $this->signInWithRole($viewer);
        $this->getJson($list)->assertForbidden();
        $this->postJson($store)->assertForbidden();

        $this->signInAsTeacher();
        $this->postJson($store, ['type' => 'unknown', 'access' => 'unknown'])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'type', 'access']);
        $id = $this->postJson($store, [
            'title' => 'Study notes',
            'description' => 'Passive voice practice',
            'type' => 'study-guide',
            'status' => 'published',
        ])->assertCreated()->assertJsonPath('status', 'draft')->json('id');
        $this->postJson($store, ['title' => 'Passive voice worksheet', 'type' => 'worksheet'])->assertCreated();
        $this->getJson(route('admin.library.resources.index', ['type' => 'study-guide', 'search' => 'voice', 'status' => 'draft']))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $id);
        $this->getJson(route('admin.library.resources.index', ['type' => 'unknown', 'page' => 0]))
            ->assertUnprocessable()->assertJsonValidationErrors(['type', 'page']);
        $this->putJson(route('admin.library.resources.update', $id), ['title' => 'Revised notes', 'type' => 'book'])
            ->assertOk()->assertJsonPath('title', 'Revised notes')->assertJsonPath('type', 'book');
        $this->assertDatabaseHas('resources', ['id' => $id, 'title' => 'Revised notes']);
    }

    public function test_uploads_are_validated_and_replacements_preserve_the_last_committed_file(): void
    {
        $disk = Storage::fake(StoredFile::DISK);
        $this->signInAsTeacher();
        $store = route('admin.library.resources.store');
        $metadata = ['title' => 'Study notes', 'type' => 'pdf', 'access' => 'registered'];

        foreach ([
            $this->uploadedContent('renamed.pdf', '<html>Not a PDF</html>'),
            UploadedFile::fake()->createWithContent('wrong.txt', '%PDF-1.7'),
            UploadedFile::fake()->create('oversized.pdf', 10241, 'application/pdf'),
        ] as $invalid) {
            $this->postJson($store, $metadata + ['file' => $invalid])
                ->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->assertSame([], $disk->allFiles());

        $id = $this->postJson($store, $metadata + ['file' => $this->pdf()])
            ->assertCreated()->assertJsonMissingPath('stored_file.path')->json('id');
        $resource = Resource::findOrFail($id);
        $original = $resource->storedFile;
        $disk->assertExists($original->path);
        $this->assertSame([], Storage::disk('public')->files('resources'));
        $update = route('admin.library.resources.update', $id);
        $this->putJson($update, $metadata + ['stored_file_id' => 99])
            ->assertOk()->assertJsonPath('stored_file_id', $original->id);

        Resource::updating(function (Resource $resource): void {
            if ($resource->title === 'Reject save') {
                throw ValidationException::withMessages(['title' => 'Rejected by test.']);
            }
        });
        $this->putJson($update, ['title' => 'Reject save', 'type' => 'pdf', 'file' => $this->pdf()])
            ->assertUnprocessable();
        $this->assertSame([$original->path], $disk->allFiles());
        $this->assertSame($original->id, $resource->fresh()->stored_file_id);
        $this->assertDatabaseCount('stored_files', 1);

        $this->putJson($update, $metadata + ['file' => $this->pdf()])->assertOk();
        $replacement = $resource->fresh()->storedFile;
        $this->assertNotSame($original->id, $replacement->id);
        $disk->assertMissing($original->path);
        $disk->assertExists($replacement->path);
        $this->assertDatabaseCount('stored_files', 1);
    }

    public function test_file_responses_enforce_access_and_return_the_requested_disposition(): void
    {
        $disk = Storage::fake(StoredFile::DISK);
        $this->signInAsTeacher();
        $id = $this->postJson(route('admin.library.resources.store'), [
            'title' => 'Study notes', 'type' => 'pdf', 'access' => 'registered', 'file' => $this->pdf(),
        ])->assertCreated()->json('id');
        $resource = Resource::findOrFail($id);
        $inline = route('library.resources.file', [$resource, 'inline']);
        $download = route('library.resources.file', [$resource, 'attachment']);
        $this->get($inline)->assertNotFound();
        $resource->forceFill(['status' => CurriculumStatus::Published])->save();

        auth()->logout();
        $this->get($inline)->assertNotFound();
        $this->get($download)->assertNotFound();
        $this->actingAs(User::factory()->create());
        $response = $this->get($inline)->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename=study-notes.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->get($download)->assertOk()->assertDownload('study-notes.pdf');
        $this->get(route('library.resources.file', [$resource, 'invalid']))->assertNotFound();

        $resource->update(['access' => 'premium']);
        $this->get($download)->assertNotFound();
        $resource->update(['access' => 'public']);
        auth()->logout();
        $this->get($inline)->assertOk();
        $disk->delete($resource->storedFile->path);
        $this->get($inline)->assertNotFound();
    }

    public function test_publication_and_learning_links_control_file_visibility(): void
    {
        Storage::fake(StoredFile::DISK);
        $this->signInAsTeacher();
        $store = route('admin.library.resources.store');
        $metadata = ['title' => 'Study notes', 'type' => 'pdf'];
        $draft = $this->postJson($store, $metadata)->assertCreated()->json('id');
        $this->patchJson(route('admin.library.resources.status', $draft), ['status' => 'published'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $id = $this->postJson($store, $metadata + ['file' => $this->pdf()])->assertCreated()->json('id');
        $status = route('admin.library.resources.status', $id);
        $links = route('admin.library.resources.links', $id);
        $standalone = route('library.resources.file', [$id, 'inline']);

        $level = Level::create(['title' => 'Path']);
        $unit = $level->units()->create(['title' => 'Unit']);
        $category = $unit->categories()->create(['title' => 'Grammar']);
        $lesson = $category->lessons()->create(['title' => 'Lesson']);
        $unlinked = $category->lessons()->create(['title' => 'Another lesson']);
        $unit->forceFill(['status' => CurriculumStatus::Published])->save();
        $lesson->forceFill(['status' => CurriculumStatus::Published])->save();
        $unlinked->forceFill(['status' => CurriculumStatus::Published])->save();
        $attachment = route('library.lessons.resources.file', [$lesson, $id, 'attachment']);

        $this->putJson($links, ['level_ids' => [[$level->id]], 'lesson_ids' => [999]])
            ->assertUnprocessable()->assertJsonValidationErrors(['level_ids.0', 'lesson_ids.0']);
        $this->putJson($links, ['level_ids' => [$level->id], 'lesson_ids' => [$lesson->id, $lesson->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('lesson_ids.1');
        $placement = ['level_ids' => [$level->id], 'lesson_ids' => [$lesson->id]];
        $this->putJson($links, $placement)->assertNoContent();
        $this->putJson($links, $placement)->assertNoContent();
        $this->assertDatabaseCount('lesson_resource', 1);
        $this->getJson(route('admin.library.resources.index', ['level_id' => $level->id]))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $id);
        $this->patchJson($status, ['status' => 'published'])->assertNoContent();
        $this->get($standalone)->assertNotFound();
        $level->forceFill(['status' => CurriculumStatus::Published])->save();

        $this->get($standalone)->assertOk();
        $this->get($attachment)->assertOk()->assertDownload('study-notes.pdf');
        $this->get(route('library.lessons.resources.file', [$unlinked, $id, 'attachment']))->assertNotFound();
        $unit->forceFill(['status' => CurriculumStatus::Draft])->save();
        $this->get($attachment)->assertNotFound();
        $this->get($standalone)->assertOk();
        $unit->forceFill(['status' => CurriculumStatus::Published])->save();

        $this->putJson(route('admin.library.resources.update', $id), $metadata + ['access' => 'points'])->assertOk();
        $this->get($attachment)->assertNotFound();
        $this->putJson(route('admin.library.resources.update', $id), $metadata + ['access' => 'public'])->assertOk();
        $this->patchJson($status, ['status' => 'draft'])->assertNoContent();
        $this->get($attachment)->assertNotFound();
        $this->patchJson($status, ['status' => 'published'])->assertNoContent();
        $this->patchJson($status, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($status, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($status, ['status' => 'published'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->get($standalone)->assertNotFound();
        $this->get($attachment)->assertNotFound();

        $this->actingAs(User::factory()->create());
        $this->putJson($links, $placement)->assertForbidden();
        $this->patchJson($status, ['status' => 'draft'])->assertForbidden();
    }

    private function pdf(): UploadedFile
    {
        return $this->uploadedContent('guide.pdf', "%PDF-1.7\n");
    }

    private function uploadedContent(string $name, string $content): UploadedFile
    {
        $file = UploadedFile::fake()->createWithContent($name, $content);
        $this->uploadFixtures[] = $file;

        return new UploadedFile($file->getPathname(), $name, 'application/pdf', test: true);
    }

    private function signInAsTeacher(): void
    {
        $this->signInWithRole(Role::where('key', Role::TEACHER)->firstOrFail());
    }

    private function signInWithRole(Role $role): void
    {
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user);
    }
}
