<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Resource;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\CurriculumStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourcePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_ui_persists_uploads_links_and_publication_with_validation_feedback(): void
    {
        Storage::fake(StoredFile::DISK);
        $this->withHeader('Accept', 'text/html');
        $index = route('admin.library.resources.index');
        $create = route('admin.library.resources.create');
        $store = route('admin.library.resources.store');
        $this->get($index)->assertRedirect(route('account.login.show'));
        $this->signIn(Role::STUDENT);
        $this->get($create)->assertForbidden();
        $this->post($store)->assertForbidden();

        $this->signIn(Role::TEACHER);
        $this->get($index)->assertOk()->assertSee('No resources yet')->assertSee($create);
        $this->get($create)->assertOk()->assertSee('multipart/form-data');
        $input = ['title' => 'My new worksheet', 'type' => 'worksheet', 'description' => 'Kept description'];
        $this->post($store, $input + ['file' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain')])
            ->assertRedirect($create)->assertSessionHasErrors('file');
        $this->get($create)->assertOk()->assertSee('Kept description')->assertSee('id="file-error"', false);

        $level = Level::create(['title' => 'Learning path']);
        $this->post($store, $input + [
            'file' => UploadedFile::fake()->create('notes.pdf', 2, 'application/pdf'),
            'level_ids' => [$level->id],
            'lesson_ids' => '',
        ])->assertStatus(303);
        $resource = Resource::sole();
        $edit = route('admin.library.resources.edit', $resource);
        $update = route('admin.library.resources.update', $resource);
        $status = route('admin.library.resources.status', $resource);
        $this->assertSame([$level->id], $resource->levels->modelKeys());
        $this->get($edit)->assertOk()->assertSee('Current file')->assertSee('Resource saved.');
        $this->put($update, $input + ['level_ids' => ''])->assertStatus(303)->assertRedirect($edit);
        $this->assertFalse($resource->levels()->exists());
        $this->patch($status, ['status' => 'published', 'return_to' => 'editor'])->assertStatus(303)->assertRedirect($edit);
        $this->get(route('admin.library.resources.index', ['status' => 'published', 'search' => 'worksheet']))
            ->assertOk()->assertSee('My new worksheet');
        $this->patch($status, ['status' => 'archived'])->assertStatus(303)->assertRedirect($index);
        $this->get(route('library.resources.show', $resource))->assertNotFound();
    }

    public function test_catalogue_detail_and_viewer_use_visible_records_and_keep_the_origin(): void
    {
        $disk = Storage::fake(StoredFile::DISK);
        $level = Level::create(['title' => 'Learning path']);
        $level->forceFill(['status' => CurriculumStatus::Published])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => CurriculumStatus::Published])->save();
        $category = $unit->categories()->create(['title' => 'Grammar']);
        $lesson = $category->lessons()->create(['title' => 'Attached lesson']);
        $lesson->forceFill(['status' => CurriculumStatus::Published])->save();
        $unrelated = $category->lessons()->create(['title' => 'Unrelated lesson']);
        $unrelated->forceFill(['status' => CurriculumStatus::Published])->save();
        $index = route('library.resources.index');
        $this->get($index)->assertOk()->assertSee('No resources available yet');
        $public = $this->resource('Visible worksheet', 'public');
        $registered = $this->resource('Members guide', 'registered');
        $restricted = $this->resource('Premium guide', 'premium');
        $public->levels()->attach($level);
        $registered->lessons()->attach($lesson);
        $disk->put($public->storedFile->path, "%PDF-1.7\n");
        $disk->put($registered->storedFile->path, "%PDF-1.7\n");
        $detail = route('library.resources.show', $public);
        $document = route('library.resources.document', $public);
        $this->get($index)->assertOk()->assertSee($detail)->assertSee('Visible worksheet')->assertDontSee('Members guide')->assertDontSee('Premium guide');
        $this->get(route('library.resources.index', ['search' => 'worksheet', 'type' => 'worksheet', 'level_id' => $level->id]))
            ->assertOk()->assertSee('Visible worksheet');
        $this->get(route('library.resources.index', ['search' => 'missing']))->assertOk()->assertSee('No matching content');
        $this->get(route('library.resources.index', ['type' => 'invalid']))->assertRedirect($index)->assertSessionHasErrors('type');
        $this->get(route('library.resources.show', $registered))->assertNotFound();
        $this->get(route('library.resources.document', $restricted))->assertNotFound();
        $this->get($detail)->assertOk()->assertSee($document)->assertSee('Learning path');
        $this->get($document)->assertOk()->assertSee($detail)->assertSee('Back to resource')
            ->assertSee(route('library.resources.file', [$public, 'inline']))->assertSee('Inline viewing is unavailable.');

        $lessonUrl = route('curriculum.lesson', [$level, $unit, $category, $lesson]);
        $lessonDocument = route('library.lessons.resources.document', [$lesson, $registered]);
        $this->get($lessonUrl)->assertOk()->assertDontSee($lessonDocument);
        $this->get($lessonDocument)->assertNotFound();
        $this->signIn(Role::STUDENT);
        $this->get($lessonUrl)->assertOk()->assertSee($lessonDocument);
        $this->get($lessonDocument)->assertOk()->assertSee($lessonUrl)->assertSee('Back to lesson')
            ->assertSee(route('library.lessons.resources.file', [$lesson, $registered, 'attachment']));
        $this->get(route('library.lessons.resources.document', [$unrelated, $registered]))->assertNotFound();
        $disk->delete($public->storedFile->path);
        $this->get($document)->assertOk()->assertSee('This file is currently unavailable.')->assertDontSee('Download PDF');

        $this->signIn(Role::TEACHER);
        $editor = route('admin.curriculum.lessons.relationships', [$category, $lesson]);
        $save = route('admin.curriculum.lessons.next-lessons.update', [$category, $lesson]);
        $this->get($editor)->assertOk()->assertSee('Members guide');
        $this->from($editor)->put($save, ['next_lesson_ids' => '', 'resource_ids' => [$public->id]])
            ->assertStatus(303)->assertRedirect($editor);
        $this->assertSame([$public->id], $lesson->resources()->get()->modelKeys());
        $this->put($save, ['next_lesson_ids' => '', 'resource_ids' => ''])->assertStatus(303);
        $this->assertFalse($lesson->resources()->exists());
    }

    private function resource(string $title, string $access): Resource
    {
        $resource = Resource::create(['title' => $title, 'type' => 'worksheet', 'access' => $access]);
        $resource->storedFile()->associate(StoredFile::create([
            'disk' => StoredFile::DISK, 'path' => "resources/{$resource->id}.pdf", 'mime_type' => 'application/pdf', 'size' => 9,
        ]));
        $resource->forceFill(['status' => CurriculumStatus::Published])->save();

        return $resource;
    }

    private function signIn(string $role): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $role)->sole());
        $this->actingAs($user);
    }
}
