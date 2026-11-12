<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Level;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Support\Ability;
use App\Support\CurriculumStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_writes_require_curriculum_management_and_validate_details(): void
    {
        $store = route('admin.curriculum.levels.store');
        $this->postJson($store, [])->assertUnauthorized();

        $viewer = Role::create(['name' => 'Admin area only']);
        $viewer->abilities()->create(['ability' => Ability::EnterAdministration->value]);
        $this->actingAs($this->userWithRole($viewer))->postJson($store, [])->assertForbidden();

        $this->signInAsManager(Role::TEACHER);
        $this->postJson($store, ['title' => []])->assertUnprocessable()->assertJsonValidationErrors('title');

        $id = $this->postJson($store, [
            'title' => 'General English',
            'description' => 'Everyday English',
            'status' => 'published',
            'position' => 1,
        ])->assertCreated()->assertJsonPath('status', 'draft')->assertJsonPath('position', null)->json('id');

        $this->putJson(route('admin.curriculum.levels.update', $id), [
            'title' => 'General English A2',
            'description' => null,
        ])->assertOk()->assertJsonPath('title', 'General English A2');

        $this->assertDatabaseHas('levels', ['id' => $id, 'title' => 'General English A2', 'description' => null]);
        $this->assertFalse(Level::visibleToPublic()->whereKey($id)->exists());
    }

    public function test_unit_and_category_writes_validate_required_parents_and_preserve_order_on_edits(): void
    {
        $this->signInAsManager();
        $level = Level::create(['title' => 'Level A']);
        $destination = Level::create(['title' => 'Level B']);
        $unitStore = route('admin.curriculum.units.store');
        $this->postJson($unitStore, ['title' => 'Reading'])->assertUnprocessable()->assertJsonValidationErrors('level_id');
        $this->postJson($unitStore, ['title' => 'Reading', 'level_id' => 9999])->assertUnprocessable()->assertJsonValidationErrors('level_id');
        $unitId = $this->postJson($unitStore, ['title' => 'Reading', 'level_id' => $level->id])
            ->assertCreated()->assertJsonPath('status', 'draft')->json('id');

        $unit = $level->units()->findOrFail($unitId);
        $unit->forceFill(['position' => 1])->save();
        $unitUpdate = route('admin.curriculum.units.update', $unit);
        $this->putJson($unitUpdate, ['title' => 'Reading A', 'level_id' => $level->id])
            ->assertOk()->assertJsonPath('position', 1);
        $this->putJson($unitUpdate, ['title' => 'Reading B', 'level_id' => $destination->id])
            ->assertOk()->assertJsonPath('level_id', $destination->id)->assertJsonPath('position', null);

        $categoryStore = route('admin.curriculum.categories.store');
        $this->postJson($categoryStore, ['title' => 'Grammar'])->assertUnprocessable()->assertJsonValidationErrors('unit_id');
        $this->postJson($categoryStore, ['title' => 'Grammar', 'unit_id' => 9999])->assertUnprocessable()->assertJsonValidationErrors('unit_id');
        $categoryId = $this->postJson($categoryStore, ['title' => 'Grammar', 'unit_id' => $unitId])
            ->assertCreated()->assertJsonPath('status', 'published')->json('id');

        $category = $unit->categories()->findOrFail($categoryId);
        $category->forceFill(['position' => 1])->save();
        $otherUnit = $level->units()->create(['title' => 'Writing']);
        $categoryUpdate = route('admin.curriculum.categories.update', $category);
        $this->putJson($categoryUpdate, ['title' => 'Applied grammar', 'unit_id' => $unitId])
            ->assertOk()->assertJsonPath('position', 1);
        $this->putJson($categoryUpdate, ['title' => 'Applied grammar', 'unit_id' => $otherUnit->id])
            ->assertOk()->assertJsonPath('unit_id', $otherUnit->id)->assertJsonPath('position', null);
        $this->assertTrue($otherUnit->categories()->whereKey($categoryId)->exists());
    }

    public function test_level_and_unit_statuses_control_public_visibility_through_ancestry(): void
    {
        $this->signInAsManager();
        $level = Level::create(['title' => 'Level A']);
        $otherLevel = Level::create(['title' => 'Level B']);
        $unit = $level->units()->create(['title' => 'Unit A']);
        $category = $unit->categories()->create(['title' => 'Grammar']);
        $levelStatus = route('admin.curriculum.levels.status', $level);
        $unitStatus = route('admin.curriculum.units.status', [$level, $unit]);

        $this->patchJson($levelStatus, ['status' => 'unknown'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->patchJson(route('admin.curriculum.units.status', [$otherLevel, $unit]), ['status' => 'published'])->assertNotFound();
        $this->patchJson($unitStatus, ['status' => 'published'])->assertNoContent();
        $this->assertFalse(Unit::visibleToPublic()->whereKey($unit)->exists());
        $this->assertFalse(Category::visibleToPublic()->whereKey($category)->exists());

        $this->patchJson($levelStatus, ['status' => 'published'])->assertNoContent();
        $this->assertSame([$level->id], Level::visibleToPublic()->ordered()->pluck('id')->all());
        $this->assertTrue($level->units()->visibleToPublic()->whereKey($unit)->exists());
        $this->assertTrue($unit->categories()->visibleToPublic()->whereKey($category)->exists());

        $this->patchJson($unitStatus, ['status' => 'draft'])->assertNoContent();
        $this->assertFalse(Category::visibleToPublic()->whereKey($category)->exists());
        $this->patchJson($unitStatus, ['status' => 'published'])->assertNoContent();
        $this->patchJson($levelStatus, ['status' => 'draft'])->assertNoContent();
        $this->assertFalse(Category::visibleToPublic()->whereKey($category)->exists());
        $this->patchJson($levelStatus, ['status' => 'published'])->assertNoContent();
        $this->assertTrue(Category::visibleToPublic()->whereKey($category)->exists());

        $this->patchJson($unitStatus, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($unitStatus, ['status' => 'published'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertFalse(Category::visibleToPublic()->whereKey($category)->exists());

        $publishedSibling = $level->units()->create(['title' => 'Another unit']);
        $this->patchJson(route('admin.curriculum.units.status', [$level, $publishedSibling]), ['status' => 'published'])->assertNoContent();
        $this->patchJson($levelStatus, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($levelStatus, ['status' => 'archived'])->assertNoContent();
        $this->patchJson($levelStatus, ['status' => 'draft'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertFalse(Unit::visibleToPublic()->whereKey($publishedSibling)->exists());
        $this->assertSame(CurriculumStatus::Published, $publishedSibling->refresh()->status);
    }

    public function test_level_unit_and_category_reordering_preserves_sibling_scope(): void
    {
        $this->signInAsManager();
        $first = Level::create(['title' => 'Level A']);
        $second = Level::create(['title' => 'Level B']);
        $levelOrder = route('admin.curriculum.levels.reorder', $second);
        $this->patchJson($levelOrder, ['position' => 3])->assertUnprocessable()->assertJsonValidationErrors('position');
        $this->patchJson($levelOrder, ['position' => 1])->assertNoContent();
        $this->patchJson($levelOrder, ['position' => 1])->assertNoContent();
        $this->assertSame([$second->id, $first->id], Level::ordered()->pluck('id')->all());
        $this->patchJson($levelOrder, ['position' => 2])->assertNoContent();
        $this->assertSame([$first->id, $second->id], Level::ordered()->pluck('id')->all());

        $unit = $first->units()->create(['title' => 'Unit A']);
        $sibling = $first->units()->create(['title' => 'Unit B']);
        $otherUnit = $second->units()->create(['title' => 'Unit C']);
        $this->patchJson(route('admin.curriculum.units.reorder', [$second, $sibling]), ['position' => 1])->assertNotFound();
        $this->patchJson(route('admin.curriculum.units.reorder', [$first, $sibling]), ['position' => 1])->assertNoContent();
        $this->assertSame([$sibling->id, $unit->id], $first->units()->ordered()->pluck('id')->all());
        $this->assertNull($otherUnit->refresh()->position);

        $grammar = $unit->categories()->create(['title' => 'Grammar']);
        $reading = $unit->categories()->create(['title' => 'Reading']);
        $elsewhere = $otherUnit->categories()->create(['title' => 'Grammar elsewhere']);
        $this->patchJson(route('admin.curriculum.categories.reorder', [$otherUnit, $reading]), ['position' => 1])->assertNotFound();
        $this->patchJson(route('admin.curriculum.categories.reorder', [$unit, $reading]), ['position' => 1])->assertNoContent();
        $this->assertSame([$reading->id, $grammar->id], $unit->categories()->ordered()->pluck('id')->all());
        $this->assertNull($elsewhere->refresh()->position);
    }

    public function test_category_archive_is_scoped_and_keeps_the_record(): void
    {
        $this->signInAsManager();
        $level = Level::create(['title' => 'Level A']);
        $unit = $level->units()->create(['title' => 'Unit A']);
        $otherUnit = $level->units()->create(['title' => 'Unit B']);
        $category = $unit->categories()->create(['title' => 'Grammar']);
        $archive = route('admin.curriculum.categories.archive', [$unit, $category]);

        $this->patchJson(route('admin.curriculum.levels.status', $level), ['status' => 'published'])->assertNoContent();
        $this->patchJson(route('admin.curriculum.units.status', [$level, $unit]), ['status' => 'published'])->assertNoContent();
        $this->assertTrue(Category::visibleToPublic()->whereKey($category)->exists());

        $this->actingAs($this->userWithRole(Role::where('key', Role::STUDENT)->sole()))
            ->patchJson($archive)->assertForbidden();
        $this->signInAsManager();
        $this->patchJson(route('admin.curriculum.categories.archive', [$otherUnit, $category]))->assertNotFound();
        $this->patchJson($archive)->assertNoContent();
        $this->patchJson($archive)->assertNoContent();
        $this->assertFalse(Category::visibleToPublic()->whereKey($category)->exists());
        $this->assertSame(CurriculumStatus::Archived, $category->refresh()->status);
    }

    public function test_database_rejects_an_orphan_unit(): void
    {
        $this->expectException(QueryException::class);

        Unit::create(['title' => 'Orphan', 'level_id' => 9999]);
    }

    public function test_database_rejects_duplicate_positions_within_a_unit(): void
    {
        $level = Level::create(['title' => 'Level A']);
        $unit = $level->units()->create(['title' => 'Unit A']);
        $otherUnit = $level->units()->create(['title' => 'Unit B']);
        $unit->categories()->create(['title' => 'Grammar'])->forceFill(['position' => 1])->save();
        $otherUnit->categories()->create(['title' => 'Grammar'])->forceFill(['position' => 1])->save();

        $this->expectException(QueryException::class);

        $unit->categories()->create(['title' => 'Writing'])->forceFill(['position' => 1])->save();
    }

    private function signInAsManager(string $key = Role::ADMINISTRATOR): void
    {
        $this->actingAs($this->userWithRole(Role::where('key', $key)->sole()));
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
