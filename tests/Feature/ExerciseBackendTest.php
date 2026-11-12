<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Queries\VisibleExercises;
use App\Support\CurriculumStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ExerciseBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_validates_and_saves_exercise_instructions_and_placement(): void
    {
        $store = route('admin.curriculum.exercises.store');
        $this->postJson($store)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson($store)->assertForbidden();
        $this->signInAsTeacher();

        $level = Level::create(['title' => 'Level']);
        $first = $level->units()->create(['title' => 'Unit one']);
        $second = $level->units()->create(['title' => 'Unit two']);
        $this->postJson($store, ['unit_id' => 9999])
            ->assertUnprocessable()->assertJsonValidationErrors(['unit_id', 'title', 'instructions']);
        $this->assertDatabaseCount('exercises', 0);

        $id = $this->postJson($store, [
            'unit_id' => $first->id,
            'title' => 'Passive voice',
            'instructions' => 'Answer each question.',
            'status' => 'published',
            'position' => 7,
        ])->assertCreated()
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('position', null)
            ->json('id');

        $exercise = $first->exercises()->findOrFail($id);
        $exercise->forceFill(['position' => 1])->save();
        $update = route('admin.curriculum.exercises.update', $exercise);
        $this->putJson($update, [
            'unit_id' => $first->id,
            'title' => 'Passive voice',
            'instructions' => 'Read all the options.',
        ])->assertOk()->assertJsonPath('position', 1);
        $this->putJson($update, [
            'unit_id' => $second->id,
            'title' => 'Passive voice review',
            'instructions' => 'Choose the best answer.',
        ])->assertOk()->assertJsonPath('position', null);

        $this->assertDatabaseHas('exercises', [
            'id' => $id,
            'unit_id' => $second->id,
            'title' => 'Passive voice review',
            'instructions' => 'Choose the best answer.',
        ]);
        $this->actingAs(User::factory()->create())->putJson($update)->assertForbidden();
    }

    public function test_management_changes_status_and_reorders_only_within_the_bound_unit(): void
    {
        $level = Level::create(['title' => 'Level']);
        $unit = $level->units()->create(['title' => 'Unit']);
        $otherUnit = $level->units()->create(['title' => 'Other unit']);
        $data = ['title' => 'Exercise', 'instructions' => 'Complete the exercise.'];
        $first = $unit->exercises()->create($data);
        $second = $unit->exercises()->create($data);
        $other = $otherUnit->exercises()->create($data);
        $status = route('admin.curriculum.exercises.status', [$unit, $first]);
        $reorder = route('admin.curriculum.exercises.reorder', [$unit, $second]);

        $this->actingAs(User::factory()->create());
        $this->patchJson($status, ['status' => 'published'])->assertForbidden();
        $this->patchJson($reorder, ['position' => 1])->assertForbidden();

        $this->signInAsTeacher();
        $this->patchJson(route('admin.curriculum.exercises.reorder', [$unit, $other]), ['position' => 1])->assertNotFound();
        $this->patchJson($reorder, ['position' => 3])->assertUnprocessable()->assertJsonValidationErrors('position');
        $this->patchJson($reorder, ['position' => 1])->assertNoContent();
        $this->assertSame([$second->id, $first->id], $unit->exercises()->ordered()->pluck('id')->all());
        $this->assertNull($other->refresh()->position);

        foreach (['published', 'draft', 'archived'] as $state) {
            $this->patchJson($status, ['status' => $state])->assertNoContent();
            $this->assertSame($state, $first->refresh()->status->value);
        }
        $this->patchJson($status, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($status, ['status' => 'published'])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_catalogue_and_detail_require_a_published_exercise_and_published_ancestry(): void
    {
        $published = ['status' => CurriculumStatus::Published];
        $level = Level::create(['title' => 'Published level']);
        $level->forceFill($published)->save();
        $hiddenLevel = Level::create(['title' => 'Draft level']);
        $unit = $level->units()->create(['title' => 'Published unit']);
        $unit->forceFill($published)->save();
        $hiddenUnit = $level->units()->create(['title' => 'Draft unit']);
        $unitUnderHiddenLevel = $hiddenLevel->units()->create(['title' => 'Published unit under a draft level']);
        $unitUnderHiddenLevel->forceFill($published)->save();
        $data = ['title' => 'Exercise', 'instructions' => 'Complete the exercise.'];
        $unit->exercises()->create($data);
        $hiddenUnit->exercises()->create($data)->forceFill($published)->save();
        $hidden = $unitUnderHiddenLevel->exercises()->create($data);
        $hidden->forceFill($published)->save();
        $visible = $unit->exercises()->create($data);
        $visible->forceFill($published)->save();

        $catalogue = app(VisibleExercises::class);
        $page = $catalogue->paginate(perPage: 1);
        $this->assertSame(1, $page->total());
        $this->assertTrue($page->items()[0]->is($visible));
        $this->assertSame(1, $catalogue->paginate($level->id)->total());
        $this->assertSame(0, $catalogue->paginate($hiddenLevel->id)->total());
        $this->assertTrue($catalogue->findOrFail($visible->id)->is($visible));
        $this->assertTrue(Gate::allows('view', $visible));
        $this->assertSame(404, Gate::inspect('view', $hidden)->status());

        $this->expectException(ModelNotFoundException::class);
        $catalogue->findOrFail($hidden->id);
    }

    public function test_database_rejects_duplicate_positions_in_the_same_unit(): void
    {
        $level = Level::create(['title' => 'Level']);
        $unit = $level->units()->create(['title' => 'Unit']);
        $data = ['title' => 'Exercise', 'instructions' => 'Complete the exercise.'];
        $unit->exercises()->create($data)->forceFill(['position' => 1])->save();

        $this->expectException(QueryException::class);
        $unit->exercises()->create($data)->forceFill(['position' => 1])->save();
    }

    private function signInAsTeacher(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher);
    }
}
