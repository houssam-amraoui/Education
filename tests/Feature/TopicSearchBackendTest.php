<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Resource;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\Topic;
use App\Models\User;
use App\Models\Video;
use App\Queries\GlobalSearch;
use App\Queries\VisibleTopics;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicSearchBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_validates_associations_and_preserves_them_on_archive(): void
    {
        $store = route('admin.library.topics.store');
        $this->postJson($store)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson($store)->assertForbidden();

        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher);

        $video = $this->video('Listening video');
        $id = $this->postJson($store, ['title' => 'Listening', 'video_ids' => [$video->id], 'status' => 'published'])
            ->assertCreated()->assertJsonPath('status', 'draft')->json('id');
        $topic = Topic::findOrFail($id);
        $update = route('admin.library.topics.update', $topic);
        $status = route('admin.library.topics.status', $topic);

        $this->putJson($update, ['title' => 'Invalid', 'video_ids' => [$video->id, $video->id], 'lesson_ids' => [9999]])
            ->assertUnprocessable()->assertJsonValidationErrors(['video_ids.1', 'lesson_ids.0']);
        $this->assertSame('Listening', $topic->refresh()->title);
        $this->putJson($update, ['title' => 'Listening practice'])->assertOk();
        $this->assertTrue($topic->videos()->sole()->is($video));

        $this->patchJson($status, ['status' => 'published'])->assertNoContent();
        $this->patchJson($status, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($status, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($status, ['status' => 'published'])->assertUnprocessable();
        $this->assertTrue($topic->videos()->sole()->is($video));
    }

    public function test_topic_index_and_detail_apply_the_same_level_and_access_rules(): void
    {
        [$level, , $category] = $this->curriculum();
        $lesson = $category->lessons()->make(['title' => 'Public lesson']);
        $lesson->forceFill(['status' => CurriculumStatus::Published])->save();
        $video = $this->video('Registered video', 'registered');
        $otherLevel = Level::create(['title' => 'Other level']);
        $otherLevel->forceFill(['status' => CurriculumStatus::Published])->save();
        $video->levels()->attach($otherLevel);
        $topic = Topic::create(['title' => 'Listening']);
        $topic->forceFill(['status' => CurriculumStatus::Published])->save();
        $topic->lessons()->attach($lesson);
        $topic->videos()->attach($video);

        $topics = app(VisibleTopics::class);
        $student = User::factory()->hasAttached(Role::where('key', Role::STUDENT)->sole())->create();

        $this->assertSame(1, $topics->paginate(levelId: $level->id)->total());
        $this->assertSame(0, $topics->paginate(levelId: $otherLevel->id)->total());
        $this->assertSame(1, $topics->paginate($student, $otherLevel->id)->total());
        $this->assertTrue($topics->findOrFail($topic->id)->lessons->sole()->is($lesson));
        $this->assertEmpty($topics->findOrFail($topic->id)->videos);
        $this->assertEmpty($topics->findOrFail($topic->id, $student, $otherLevel->id)->lessons);
        $this->assertTrue($topics->findOrFail($topic->id, $student, $otherLevel->id)->videos->sole()->is($video));

        $level->forceFill(['status' => CurriculumStatus::Archived])->save();
        $this->assertSame(0, $topics->paginate()->total());
        $this->expectException(ModelNotFoundException::class);
        $topics->findOrFail($topic->id);
    }

    public function test_search_paginates_across_sources_after_applying_visibility(): void
    {
        [$level, $unit, $category] = $this->curriculum();
        $lesson = $category->lessons()->make(['title' => 'Listening A', 'body' => '<p>Body-only phrase</p>']);
        $lesson->forceFill(['status' => CurriculumStatus::Published])->save();
        $category->lessons()->create(['title' => 'Listening 0']);
        $exercise = $unit->exercises()->make(['title' => 'Listening B', 'instructions' => 'Listen and respond.']);
        $exercise->forceFill(['status' => CurriculumStatus::Published])->save();
        $unit->exams()->make(['title' => 'Listening C', 'type' => 'interactive'])
            ->forceFill(['status' => CurriculumStatus::Published])->save();
        $unit->exams()->make(['title' => 'Listening D', 'type' => 'document', 'access' => 'registered'])
            ->forceFill(['status' => CurriculumStatus::Published, 'exam_file_id' => $this->file('paper')->id, 'correction_file_id' => $this->file('correction')->id])->save();
        $resource = new Resource(['title' => 'Listening E', 'type' => 'pdf']);
        $resource->forceFill(['status' => CurriculumStatus::Published, 'stored_file_id' => $this->file('resource')->id])->save();
        $resource->levels()->attach($level);
        $this->video('Listening F');
        $this->video('Listening premium', 'premium');
        $topic = Topic::create(['title' => 'Listening G']);
        $topic->forceFill(['status' => CurriculumStatus::Published])->save();
        $topic->resources()->attach($resource);

        $search = app(GlobalSearch::class);
        $page = $search->paginate('Listening', perPage: 2);
        $this->assertSame(6, $page->total());
        $this->assertSame(['lesson', 'exercise'], array_column($page->items(), 'type'));
        $this->assertSame(['Level', 'Unit', 'Grammar'], $page->items()[0]['context']);
        $destination = $page->items()[0]['destination'];
        $this->assertSame(route('curriculum.lesson', [$level, $unit, $category, $lesson]), route($destination['route'], $destination['parameters']));

        $results = $search->paginate('Listening', User::factory()->hasAttached(Role::where('key', Role::STUDENT)->sole())->create());
        $this->assertSame(['lesson', 'exercise', 'exam', 'exam', 'resource', 'video', 'topic'], array_column($results->items(), 'type'));
        $this->assertSame(0, $search->paginate('Body-only phrase')->total());
        $this->assertSame(0, $search->paginate('%')->total());
    }

    private function curriculum(): array
    {
        $level = Level::create(['title' => 'Level']);
        $unit = $level->units()->create(['title' => 'Unit']);
        $category = $unit->categories()->create(['title' => 'Grammar']);

        foreach ([$level, $unit, $category] as $item) {
            $item->forceFill(['status' => CurriculumStatus::Published])->save();
        }

        return [$level, $unit, $category];
    }

    private function video(string $title, string $access = 'public'): Video
    {
        $video = new Video(['title' => $title, 'access' => $access]);
        $video->forceFill(['provider' => 'youtube', 'provider_id' => 'dQw4w9WgXcQ', 'status' => CurriculumStatus::Published])->save();

        return $video;
    }

    private function file(string $name): StoredFile
    {
        return StoredFile::create(['disk' => 'documents', 'path' => $name.'.pdf', 'mime_type' => 'application/pdf', 'size' => 100]);
    }
}
