<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExercisePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_pages_save_validated_forms_and_link_to_question_authoring(): void
    {
        $this->withoutVite();
        $prefix = 'admin.curriculum.exercises.';
        $index = route($prefix.'index');
        $create = route($prefix.'create');
        $headers = ['Accept' => 'text/html'];
        $this->get($index)->assertRedirect(route('account.login.show'));
        $this->actingAs(User::factory()->create())->get($create)->assertForbidden();
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher)->get($index)->assertOk()->assertSee('No exercises yet');
        $this->get($create)->assertOk()->assertSee('Create a unit before adding an exercise.');

        $level = Level::create(['title' => 'English']);
        $unit = $level->units()->create(['title' => 'Grammar']);
        $this->post(route($prefix.'store'), ['title' => 'Passive voice'], $headers)
            ->assertRedirect($create)->assertSessionHasErrors(['unit_id', 'instructions']);
        $this->get($create)->assertSee('Passive voice')->assertSee('Grammar');
        $data = ['title' => 'Passive voice', 'unit_id' => $unit->id, 'instructions' => 'Choose the right form.'];
        $saved = $this->post(route($prefix.'store'), $data, $headers)->assertStatus(303);
        $exercise = $unit->exercises()->sole();
        $edit = route($prefix.'edit', $exercise);
        $saved->assertRedirect($edit);
        $this->get($edit)->assertSee($data['instructions'])->assertSee(route($prefix.'questions.index', $exercise));
        $this->put(route($prefix.'update', $exercise), array_replace($data, ['title' => 'Revised exercise']), $headers)->assertRedirect($edit);
        $this->patch(route($prefix.'status', [$unit, $exercise]), ['status' => 'published', 'return_to' => 'editor'], $headers)->assertRedirect($edit);
        $this->get($index.'?status=published')->assertSee('Revised exercise')->assertSee('Unpublish Revised exercise');
        $this->get($index.'?search=missing')->assertSee('No matching records');
        $this->patch(route($prefix.'status', [$unit, $exercise]), ['status' => 'archived'], $headers)->assertRedirect($index);
        $this->get($index.'?status=published')->assertSee('No matching records');
    }

    public function test_catalogue_shows_only_live_exercises_and_can_filter_by_level_and_search(): void
    {
        $this->withoutVite();
        $index = route('curriculum.exercises.index');
        $this->get($index)->assertOk()->assertSee('No exercises available yet');
        $level = Level::create(['title' => 'English']);
        $level->forceFill(['status' => 'published'])->save();
        $unit = $level->units()->create(['title' => 'Grammar']);
        $unit->forceFill(['status' => 'published'])->save();
        $published = $unit->exercises()->create(['title' => 'Live exercise', 'instructions' => 'Read and answer.']);
        $published->forceFill(['status' => 'published'])->save();
        $draft = $unit->exercises()->create(['title' => 'Draft exercise', 'instructions' => 'Read and answer.']);
        $this->get(route('curriculum.level', $level))->assertSee(route('curriculum.exercises.index', ['level_id' => $level->id]));
        $this->get($index.'?level_id='.$level->id.'&search=Live')->assertOk()
            ->assertSee('Live exercise')->assertDontSee('Draft exercise');
        $this->get($index.'?search=missing')->assertSee('No matching content');
        $this->get(route('curriculum.exercises.instructions', $published))->assertSee('Read and answer.')
            ->assertSee('This assessment has no questions yet.')->assertDontSee('Start exercise');
        $this->get(route('curriculum.exercises.instructions', $draft))->assertNotFound();
    }
}
