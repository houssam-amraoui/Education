<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use App\Queries\VisibleVideos;
use App\Support\Ability;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class VideoBackendTest extends TestCase
{
    use RefreshDatabase;

    private const SOURCE = 'https://www.youtube.com/watch?feature=share&v=dQw4w9WgXcQ';

    public function test_authoring_normalizes_sources_and_saves_validated_relationships(): void
    {
        $this->signInAsTeacher();
        $level = Level::create(['title' => 'Level']);
        $unit = $level->units()->create(['title' => 'Unit']);
        $category = $unit->categories()->create(['title' => 'Grammar']);
        $lesson = $category->lessons()->create(['title' => 'Passive voice']);
        $store = route('admin.library.videos.store');

        foreach ([
            '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>',
            'https://www.youtube.com.example.test/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com@example.test/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&v=abcdefghijk',
            'https://youtu.be/invalid',
        ] as $source) {
            $this->postJson($store, ['title' => 'Video', 'source_url' => $source])
                ->assertUnprocessable()->assertJsonValidationErrors('source_url');
        }
        $this->assertDatabaseCount('videos', 0);

        $videoId = $this->postJson($store, [
            'title' => 'Video',
            'source_url' => self::SOURCE,
            'lesson_ids' => [$lesson->id],
            'level_ids' => [$level->id],
            'provider_id' => 'abcdefghijk',
            'status' => 'published',
            'position' => 1,
        ])->assertCreated()
            ->assertJsonPath('provider', 'youtube')
            ->assertJsonPath('provider_id', 'dQw4w9WgXcQ')
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('position', null)
            ->assertJsonMissingPath('source_url')
            ->json('id');

        $video = Video::findOrFail($videoId);
        $this->assertTrue($lesson->videos()->sole()->is($video));
        $this->assertTrue($level->videos()->sole()->is($video));
        $update = route('admin.library.videos.update', $video);
        $this->putJson($update, ['title' => 'Invalid', 'lesson_ids' => [$lesson->id, $lesson->id], 'level_ids' => [9999]])
            ->assertUnprocessable()->assertJsonValidationErrors(['lesson_ids.1', 'level_ids.0']);
        $this->assertSame('Video', $video->refresh()->title);

        $this->putJson($update, ['title' => 'Revised', 'source_url' => 'https://youtu.be/abcdefghijk?t=30', 'lesson_ids' => []])
            ->assertOk()->assertJsonPath('provider_id', 'abcdefghijk');
        $this->assertFalse($video->lessons()->exists());
        $this->assertTrue($video->levels()->sole()->is($level));
        $this->putJson($update, ['title' => 'Renamed'])->assertOk()->assertJsonPath('provider_id', 'abcdefghijk');
    }

    public function test_management_requires_curriculum_privileges_and_enforces_lifecycle_and_order(): void
    {
        $store = route('admin.library.videos.store');
        $first = $this->video('First');
        $second = $this->video('Second');
        $status = route('admin.library.videos.status', $first);
        $reorder = route('admin.library.videos.reorder', $second);
        $update = route('admin.library.videos.update', $first);

        $this->postJson($store)->assertUnauthorized();
        $role = Role::create(['name' => 'Admin area only']);
        $role->abilities()->create(['ability' => Ability::EnterAdministration->value]);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user);
        $this->postJson($store)->assertForbidden();
        $this->putJson($update)->assertForbidden();
        $this->patchJson($status)->assertForbidden();
        $this->patchJson($reorder)->assertForbidden();

        $this->signInAsTeacher();
        $this->patchJson($status, ['status' => 'published'])->assertNoContent();
        $this->assertSame(CurriculumStatus::Published, $first->refresh()->status);
        $this->patchJson($status, ['status' => 'draft'])->assertNoContent();
        $this->assertSame(CurriculumStatus::Draft, $first->refresh()->status);
        $this->patchJson($reorder, ['position' => 3])->assertUnprocessable()->assertJsonValidationErrors('position');
        $this->patchJson($reorder, ['position' => 1])->assertNoContent();
        $this->assertSame([$second->id, $first->id], Video::ordered()->pluck('id')->all());
        $this->patchJson($reorder, ['position' => 2])->assertNoContent();
        $this->assertSame([$first->id, $second->id], Video::ordered()->pluck('id')->all());
        $this->patchJson($status, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($status, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($status, ['status' => 'published'])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_visibility_applies_before_search_filters_and_pagination(): void
    {
        $published = Level::create(['title' => 'Published level']);
        $published->forceFill(['status' => CurriculumStatus::Published])->save();
        $draft = Level::create(['title' => 'Draft level']);
        $this->video('Grammar draft');
        $archived = $this->video('Grammar archived', status: CurriculumStatus::Archived);
        $this->video('Grammar premium', 'premium', CurriculumStatus::Published);
        $hidden = $this->video('Grammar hidden placement', status: CurriculumStatus::Published);
        $hidden->levels()->attach($draft);
        $public = $this->video('Grammar basics', status: CurriculumStatus::Published);
        $public->levels()->attach([$draft->id, $published->id]);
        $registered = $this->video('Revision', 'registered', CurriculumStatus::Published);
        $registered->update(['description' => 'More grammar practice']);
        $student = User::factory()->create();
        $catalogue = app(VisibleVideos::class);

        $results = $catalogue->paginate(null, ['search' => 'grammar'], perPage: 1);
        $this->assertSame(1, $results->total());
        $this->assertTrue($results->items()[0]->is($public));
        $this->assertSame(2, $catalogue->paginate($student, ['search' => 'grammar'])->total());
        $this->assertSame(1, $catalogue->paginate($student, ['level_id' => $published->id, 'access' => 'public'])->total());
        $this->assertSame(0, $catalogue->paginate($student, ['level_id' => $draft->id])->total());
        $this->assertSame(0, $catalogue->paginate($student, ['access' => 'premium'])->total());
        $this->assertTrue($catalogue->findOrFail(null, $public->id)->is($public));
        $this->assertTrue($catalogue->findOrFail($student, $registered->id)->is($registered));
        $this->assertSame(404, Gate::inspect('view', $registered)->status());
        $this->assertSame(404, Gate::forUser($student)->inspect('view', $archived)->status());

        $this->expectException(ModelNotFoundException::class);
        $catalogue->findOrFail(null, $registered->id);
    }

    private function signInAsTeacher(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher);
    }

    private function video(string $title, string $access = 'public', CurriculumStatus $status = CurriculumStatus::Draft): Video
    {
        $video = new Video(['title' => $title, 'access' => $access]);
        $video->forceFill(['provider' => 'youtube', 'provider_id' => 'dQw4w9WgXcQ', 'status' => $status])->save();

        return $video;
    }
}
