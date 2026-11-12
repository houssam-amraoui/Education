<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_save_video_and_manage_it_from_the_live_pages(): void
    {
        $index = route('admin.library.videos.index');
        $create = route('admin.library.videos.create');
        $store = route('admin.library.videos.store');
        $this->get($index)->assertRedirect(route('account.login.show'));
        $this->actingAs(User::factory()->create())->get($create)->assertForbidden();
        $this->signInAsTeacher();
        $this->get($index)->assertOk()->assertSee('No videos yet')->assertSee($create);
        $this->get($create)->assertOk()->assertSee('Video URL');

        $html = ['Accept' => 'text/html'];
        $this->post($store, ['title' => 'Video title', 'source_url' => 'https://example.test/video'], $html)
            ->assertRedirect($create)->assertSessionHasErrors('source_url')->assertSessionHasInput('title', 'Video title');
        $this->get($create)->assertSee('Enter a supported YouTube video URL.');
        $response = $this->post($store, [
            'title' => 'Video title',
            'source_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'level_ids' => '',
            'lesson_ids' => '',
        ], $html);
        $video = Video::sole();
        $edit = route('admin.library.videos.edit', $video);
        $response->assertStatus(303)->assertRedirect($edit);
        $this->get($edit)->assertOk()->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->get(route('library.videos.show', $video))->assertNotFound();

        $status = route('admin.library.videos.status', $video);
        $this->patch($status, ['status' => 'published', 'return_to' => 'editor'], $html)->assertRedirect($edit);
        $this->get($index.'?status=published')->assertSee('Video title')->assertSee('Unpublish');
        $this->patch($status, ['status' => 'draft'], $html)->assertRedirect($index);
        $this->get($index.'?status=published')->assertSee('No matching records')->assertDontSee('Video title');
    }

    public function test_video_and_lesson_pages_expose_only_visible_links_and_render_safe_player_states(): void
    {
        $this->signInAsTeacher();
        $level = Level::create(['title' => 'Learning path']);
        $level->forceFill(['status' => 'published'])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => 'published'])->save();
        $category = $unit->categories()->create(['title' => 'Grammar']);
        $category->forceFill(['status' => 'published'])->save();
        $lesson = $category->lessons()->create(['title' => 'Linked lesson']);
        $lesson->forceFill(['status' => 'published'])->save();
        $draft = $category->lessons()->create(['title' => 'Hidden lesson']);
        $video = new Video(['title' => 'Learning video', 'description' => 'An explanation']);
        $video->forceFill(['status' => 'published', 'provider' => 'youtube', 'provider_id' => 'dQw4w9WgXcQ'])->save();
        $video->levels()->attach($level);
        $draft->videos()->attach($video);
        $restricted = $video->replicate()->fill(['title' => 'Hidden video', 'access' => 'premium']);
        $restricted->save();
        $lessonEdit = route('admin.curriculum.lessons.edit', [$category, $lesson]);
        $this->get($lessonEdit)->assertOk()->assertSee('Embedded videos')->assertSee($video->title);
        $update = route('admin.curriculum.lessons.update', [$category, $lesson]);
        $html = ['Accept' => 'text/html'];
        $this->from($lessonEdit)->put($update, ['title' => $lesson->title, 'video_ids' => [9999]], $html)
            ->assertRedirect($lessonEdit)->assertSessionHasErrors('video_ids.0');
        $this->put($update, ['title' => $lesson->title, 'video_ids' => [$video->id, $restricted->id]], $html)->assertStatus(303);
        $this->assertCount(2, $lesson->videos()->get());

        auth()->forgetGuards();
        $index = route('library.videos.index', ['level_id' => $level->id]);
        $detail = route('library.videos.show', $video);
        $lessonPage = route('curriculum.lesson', [$level, $unit, $category, $lesson]);
        $this->get($index.'&search=explanation')->assertOk()->assertSee($detail)->assertDontSee('Hidden video');
        $this->get($index.'&search=missing')->assertOk()->assertSee('No matching content');
        $this->get($detail)->assertOk()->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->assertSee($lessonPage)->assertDontSee('Hidden lesson');
        $this->get($lessonPage)->assertOk()->assertSee($detail)->assertDontSee('Hidden video');
        $this->get(route('library.videos.show', $restricted))->assertNotFound();

        $video->forceFill(['provider_id' => 'invalid'])->save();
        $this->get($detail)->assertOk()->assertSee('Video unavailable')->assertDontSee('<iframe', false);

        $this->signInAsTeacher();
        $this->put($update, ['title' => $lesson->title, 'video_ids' => ''], $html)->assertStatus(303);
        $this->assertFalse($lesson->videos()->exists());
    }

    private function signInAsTeacher(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher);
    }
}
