<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Role;
use App\Models\Topic;
use App\Models\User;
use App\Support\CurriculumStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_editor_and_relationship_form_connect_to_the_learner_index(): void
    {
        $index = route('admin.library.topics.index');
        $create = route('admin.library.topics.create');
        $store = route('admin.library.topics.store');
        $publicIndex = route('curriculum.topics.index');
        [$level, $unit, $category] = $this->publishedCurriculum();
        $lesson = $category->lessons()->make(['title' => 'Compare two ideas']);
        $lesson->forceFill(['status' => CurriculumStatus::Published])->save();

        $this->get($index)->assertRedirect(route('account.login.show'));
        $this->actingAs(User::factory()->create())->get($create)->assertForbidden();

        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher)->withHeader('Accept', 'text/html');

        $this->get($index)->assertOk()->assertSee('No topics yet')->assertSee($create);
        $this->get($create)->assertOk()->assertSee('Save topic');
        $this->post($store, [])->assertRedirect($create)->assertSessionHasErrors('title');
        $this->post($store, ['title' => 'Comparisons', 'description' => 'Link ideas clearly.'])->assertStatus(303);

        $topic = Topic::sole();
        $edit = route('admin.library.topics.edit', $topic);
        $links = route('admin.library.topics.relationships', $topic);
        $updateLinks = route('admin.library.topics.relationships.update', $topic);
        $detail = route('curriculum.topics.show', ['topic' => $topic, 'level_id' => $level->id]);

        $this->get($edit)->assertOk()->assertSee('Comparisons')->assertSee($links);
        $this->get($links)->assertOk()->assertSee($lesson->title);
        $this->put($updateLinks, ['lesson_ids' => [$lesson->id, 9999]])
            ->assertRedirect($links)->assertSessionHasErrors('lesson_ids.1');
        $this->get($links)->assertOk()->assertSee('field-error')->assertSee('checked');
        $this->put($updateLinks, ['lesson_ids' => [$lesson->id]])->assertRedirect($links);
        $this->get($detail)->assertNotFound();
        $this->patch(route('admin.library.topics.status', $topic), ['status' => 'published', 'return_to' => 'editor'])
            ->assertRedirect($edit);

        $this->get(route('curriculum.level', $level))->assertOk()->assertSee($publicIndex.'?level_id='.$level->id);
        $this->get($publicIndex.'?level_id='.$level->id)->assertOk()->assertSee($detail)->assertSee('1 learning resource');
        $this->get($detail)->assertOk()->assertSee($lesson->title)
            ->assertSee(route('curriculum.lesson', [$level, $unit, $category, $lesson]));

        $this->put($updateLinks, ['lesson_ids' => ''])->assertRedirect($links);
        $this->assertFalse($topic->lessons()->exists());
        $this->get($detail)->assertNotFound();
    }

    public function test_search_form_preserves_filters_and_links_to_live_results(): void
    {
        [$level, $unit, $category] = $this->publishedCurriculum();
        for ($number = 1; $number <= 13; $number++) {
            $category->lessons()->make(['title' => sprintf('Comparisons %02d', $number)])
                ->forceFill(['status' => CurriculumStatus::Published])->save();
        }
        $category->lessons()->create(['title' => 'Comparisons hidden']);
        $topic = Topic::create(['title' => 'Comparisons topic']);
        $topic->lessons()->attach($category->lessons()->where('title', 'Comparisons 01')->sole());
        $topic->forceFill(['status' => CurriculumStatus::Published])->save();

        $search = route('curriculum.search');
        $this->get($search)->assertOk()->assertSee('Enter a search term');
        $this->get(route('curriculum.search', ['q' => ['invalid'], 'type' => 'invalid']))
            ->assertRedirect($search)->assertSessionHasErrors(['q', 'type']);
        $this->get($search)->assertOk()->assertSee('validation-summary');
        $this->get(route('curriculum.search', ['q' => 'missing']))->assertOk()->assertSee('No matching content');

        $filters = ['q' => 'Comparisons', 'type' => 'lesson'];
        $pageTwo = route('curriculum.search', $filters + ['page' => 2]);
        $this->get(route('curriculum.search', $filters))->assertOk()->assertSee('13 results')->assertSee($pageTwo)
            ->assertDontSee('Comparisons topic')->assertDontSee('Comparisons hidden');
        $lastLesson = $category->lessons()->where('title', 'Comparisons 13')->sole();
        $this->get($pageTwo)->assertOk()->assertSee('Comparisons 13')
            ->assertSee(route('curriculum.lesson', [$level, $unit, $category, $lastLesson]));
        $this->get(route('curriculum.search', ['q' => 'Comparisons', 'type' => 'topic']))
            ->assertOk()->assertSee(route('curriculum.topics.show', $topic));
        $this->get(route('curriculum.topics.show', $topic))->assertOk();
    }

    private function publishedCurriculum(): array
    {
        $level = Level::create(['title' => 'English']);
        $unit = $level->units()->create(['title' => 'Ideas']);
        $category = $unit->categories()->create(['title' => 'Grammar']);
        foreach ([$level, $unit, $category] as $record) {
            $record->forceFill(['status' => CurriculumStatus::Published])->save();
        }

        return [$level, $unit, $category];
    }
}
