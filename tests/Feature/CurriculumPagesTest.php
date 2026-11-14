<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Support\CurriculumStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_follow_real_ancestry_and_show_empty_states(): void
    {
        $this->get('/learn/units?search=grammar')->assertMovedPermanently()->assertRedirect('/units?search=grammar');
        $this->get(route('curriculum.home'))->assertOk()->assertSee('No learning paths yet');

        $level = Level::create(['title' => 'Published path']);
        $level->forceFill(['status' => CurriculumStatus::Published])->save();
        $draft = Level::create(['title' => 'Hidden path']);
        $unit = $level->units()->create(['title' => 'Published unit']);
        $unit->forceFill(['status' => CurriculumStatus::Published])->save();
        $level->units()->create(['title' => 'Hidden unit']);
        $category = $unit->categories()->create(['title' => 'Listening']);
        $hidden = $unit->categories()->create(['title' => 'Hidden category']);
        $hidden->forceFill(['status' => CurriculumStatus::Archived])->save();

        $this->get(route('curriculum.home'))->assertOk()->assertSee('Published path')->assertDontSee('Hidden path');
        $this->get(route('curriculum.paths', ['search' => 'missing']))->assertOk()->assertSee('No matching content');
        $this->get(route('curriculum.level', $draft))->assertNotFound();
        $this->get(route('curriculum.level', $level))->assertOk()->assertSee('1 structured unit');
        $this->get(route('curriculum.level.units', $level))->assertOk()->assertSee('Published unit')->assertDontSee('Hidden unit');
        $this->get(route('curriculum.unit', [$level, $unit]))->assertOk()->assertSee('Listening')->assertDontSee('Hidden category');

        $categoryUrl = route('curriculum.category', [$level, $unit, $category]);
        $this->get($categoryUrl)->assertOk()->assertSee('No lessons yet')->assertSee('Back to unit categories')
            ->assertSeeInOrder(['Published path', 'Units', 'Published unit', 'Listening lessons']);
        $this->get(route('curriculum.category', [$level, $unit, $hidden]))->assertNotFound();
        $otherUnit = $level->units()->create(['title' => 'Other unit']);
        $otherUnit->forceFill(['status' => CurriculumStatus::Published])->save();
        $this->get(route('curriculum.category', [$level, $otherUnit, $category]))->assertNotFound();

        $level->forceFill(['status' => CurriculumStatus::Draft])->save();
        $this->get($categoryUrl)->assertNotFound();
        $this->assertSame('/units', route('curriculum.units', absolute: false));
        $this->get('/units')->assertOk()->assertSee('No units yet');
    }

    public function test_html_management_requires_authorization_and_returns_validation_and_feedback(): void
    {
        $this->withHeaders(['Accept' => 'text/html']);
        $levels = route('admin.curriculum.levels.index');
        $this->get($levels)->assertRedirect(route('account.login.show'));
        $student = User::factory()->create();
        $student->roles()->attach(Role::where('key', Role::STUDENT)->sole());
        $this->actingAs($student)->get($levels)->assertForbidden()->assertSee('Access restricted');

        $this->signInAsTeacher();
        $this->get($levels)->assertOk()->assertSee('No learning paths yet');
        $create = route('admin.curriculum.levels.create');
        $this->get($create)->assertOk()->assertSee('Save learning path');
        $this->from($create)->post(route('admin.curriculum.levels.store'), ['description' => 'Kept after validation'])
            ->assertRedirect($create)->assertSessionHasErrors('title');
        $this->get($create)->assertOk()->assertSee('Kept after validation')->assertSee('The title field is required.');
        $this->post(route('admin.curriculum.levels.store'), ['title' => 'General English'])
            ->assertStatus(303)->assertRedirect($levels);
        $level = Level::where('title', 'General English')->sole();
        $this->get($levels)->assertOk()->assertSee('Level created.')->assertSee('General English')->assertSee('Draft');
        $this->put(route('admin.curriculum.levels.update', $level), ['title' => 'English A2'])->assertRedirect($levels);
        $this->patch(route('admin.curriculum.levels.status', $level), ['status' => 'published'])->assertRedirect($levels);
        $this->get(route('curriculum.paths'))->assertOk()->assertSee('English A2');
    }

    public function test_unit_and_category_forms_keep_parent_context_after_writes(): void
    {
        $this->withHeaders(['Accept' => 'text/html']);
        $this->signInAsTeacher();

        $level = Level::create(['title' => 'English A2']);
        $units = route('admin.curriculum.units.index', ['level_id' => $level->id]);
        $this->get(route('admin.curriculum.levels.edit', $level))->assertOk()->assertSee($units);
        $this->get($units)->assertOk()->assertSee('No units yet');
        $this->get(route('admin.curriculum.units.create', ['level_id' => $level->id]))
            ->assertOk()->assertSee('English A2')->assertSee('Save unit');
        $this->post(route('admin.curriculum.units.store'), ['level_id' => $level->id, 'title' => 'Everyday English'])
            ->assertStatus(303)->assertRedirect($units);

        $unit = $level->units()->sole();
        $categories = route('admin.curriculum.categories.index', ['unit_id' => $unit->id]);
        $this->get(route('admin.curriculum.units.edit', $unit))->assertOk()->assertSee($categories);
        $this->get(route('admin.curriculum.categories.create', ['unit_id' => $unit->id]))
            ->assertOk()->assertSee('Everyday English')->assertSee('Save category');
        $this->post(route('admin.curriculum.categories.store'), ['unit_id' => $unit->id, 'title' => 'Grammar'])
            ->assertStatus(303)->assertRedirect($categories);
        $grammar = $unit->categories()->sole();

        $this->post(route('admin.curriculum.categories.store'), ['unit_id' => $unit->id, 'title' => 'Reading'])
            ->assertRedirect($categories);
        $reading = $unit->categories()->where('title', 'Reading')->sole();
        $this->patch(route('admin.curriculum.categories.reorder', [$unit, $reading]), ['position' => 1])
            ->assertStatus(303)->assertRedirect($categories);
        $this->get($categories)->assertOk()->assertSeeInOrder(['Reading', 'Grammar']);
        $this->get(route('admin.curriculum.categories.edit', $grammar))->assertOk()->assertSee('Edit category')->assertSee('Grammar');
        $this->patch(route('admin.curriculum.categories.archive', [$unit, $grammar]))
            ->assertStatus(303)->assertRedirect($categories);
        $this->get(route('admin.curriculum.categories.index', ['unit_id' => $unit->id, 'status' => 'archived']))
            ->assertOk()->assertSee('Grammar')->assertDontSee('Reading');
    }

    private function signInAsTeacher(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher);
    }
}
