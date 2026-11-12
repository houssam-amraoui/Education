<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Support\ProgressPages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_relationship_form_saves_and_clears_prerequisites_and_rolls_back_invalid_changes(): void
    {
        $category = $this->category();
        $source = $category->lessons()->create(['title' => 'Source lesson']);
        $target = $category->lessons()->create(['title' => 'Preparation lesson']);
        $otherCategory = $category->unit->categories()->create(['title' => 'Other category']);
        $editor = route('admin.curriculum.lessons.relationships', [$category, $source]);
        $save = route('admin.curriculum.lessons.next-lessons.update', [$category, $source]);
        $this->actingAs($this->user(Role::TEACHER))->withHeader('Accept', 'text/html');

        $this->get($editor)->assertOk()->assertSee('name="prerequisite_lesson_ids[]"', false);
        $this->from($editor)->put($save, ['prerequisite_lesson_ids' => [$target->id], 'next_lesson_ids' => [$source->id]])
            ->assertRedirect($editor)->assertSessionHasErrors('next_lesson_ids');
        $this->assertFalse($source->prerequisiteLessons()->exists());

        $this->put($save, ['prerequisite_lesson_ids' => [$target->id], 'next_lesson_ids' => []])
            ->assertRedirect($editor)->assertStatus(303);
        $this->assertTrue($source->prerequisiteLessons()->whereKey($target->id)->exists());
        $this->from($editor)->put($save, ['prerequisite_lesson_ids' => [$source->id], 'next_lesson_ids' => []])
            ->assertRedirect($editor)->assertSessionHasErrors('prerequisite_lesson_ids');
        $this->get($editor)->assertSee('A lesson cannot require itself.');
        $this->put(route('admin.curriculum.lessons.next-lessons.update', [$otherCategory, $source]), [
            'prerequisite_lesson_ids' => '', 'next_lesson_ids' => '',
        ])->assertNotFound();
        $this->put($save, ['prerequisite_lesson_ids' => '', 'next_lesson_ids' => ''])->assertStatus(303);
        $this->assertFalse($source->prerequisiteLessons()->exists());
    }

    public function test_assessment_editors_save_validated_links_and_deny_student_writes(): void
    {
        $category = $this->category();
        $lesson = $category->lessons()->create(['title' => 'Preparation lesson']);
        $exercise = $category->unit->exercises()->create(['title' => 'Exercise', 'instructions' => 'Practise.']);
        $exam = $category->unit->exams()->create(['title' => 'Exam', 'type' => Exam::INTERACTIVE]);
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);

        foreach (['exercises' => $exercise, 'exams' => $exam] as $type => $assessment) {
            $editor = route('admin.curriculum.'.$type.'.edit', $assessment);
            $save = route('admin.curriculum.'.$type.'.prerequisites.update', $assessment);
            $this->actingAs($student)->putJson($save, ['prerequisite_lesson_ids' => [$lesson->id]])->assertForbidden();
            $this->actingAs($teacher)->withHeader('Accept', 'text/html')->get($editor)
                ->assertOk()->assertSee($save)->assertSee('Preparation lesson');
            $this->put($save, ['prerequisite_lesson_ids' => [PHP_INT_MAX]])
                ->assertRedirect($editor)->assertSessionHasErrors('prerequisite_lesson_ids');
            $this->put($save, ['prerequisite_lesson_ids' => [$lesson->id]])
                ->assertRedirect($editor)->assertStatus(303)->assertSessionHas('status', 'Prerequisite lessons saved.');
            $this->assertTrue($assessment->prerequisiteLessons()->whereKey($lesson->id)->exists());
        }
    }

    public function test_live_learning_pages_show_owned_preparation_and_recommendations(): void
    {
        $category = $this->category();
        $unit = $category->unit;
        $student = $this->user(Role::STUDENT);
        $other = $this->user(Role::STUDENT);
        $preparation = $category->lessons()->forceCreate(['title' => 'Preparation lesson', 'status' => 'published']);
        $source = $category->lessons()->forceCreate(['title' => 'Source lesson', 'status' => 'published']);
        $next = $category->lessons()->forceCreate(['title' => 'Next lesson fixture', 'status' => 'published']);
        $hidden = $category->lessons()->create(['title' => 'Hidden preparation fixture']);
        $source->nextLessons()->attach($next);
        $exercise = $unit->exercises()->forceCreate(['title' => 'Exercise', 'instructions' => 'Practise.', 'status' => 'published']);
        $exam = $unit->exams()->forceCreate(['title' => 'Exam', 'type' => Exam::INTERACTIVE, 'status' => 'published']);
        foreach ([$source, $exercise, $exam] as $content) {
            $content->prerequisiteLessons()->attach([$preparation->id, $hidden->id]);
        }
        $score = ['student_id' => $student->id, 'started_at' => now(), 'submitted_at' => now(), 'possible_points' => 10, 'awarded_points' => 5];
        $exerciseResult = route('curriculum.exercises.attempts.show', [$exercise, $exercise->attempts()->forceCreate($score)]);
        $examResult = route('curriculum.exams.attempts.show', [$exam, $exam->attempts()->forceCreate($score)]);
        $exerciseUrl = route('curriculum.exercises.instructions', $exercise);
        $examUrl = route('curriculum.exams.instructions', $exam);
        $this->withHeader('Accept', 'text/html')->get($exerciseUrl)
            ->assertOk()->assertSee('Preparation lesson')->assertDontSee('Recommended review')->assertDontSee('Hidden preparation fixture');

        $this->actingAs($student)->get(route('account.home'))->assertSee('No recommendations right now');
        $student->selectedLevels()->attach($unit->level_id);
        $preparation->progress()->forceCreate(['student_id' => $student->id, 'completed_at' => now()]);
        $this->get(ProgressPages::lessonUrl($source))->assertSee('Recommended preparation')->assertSee('Next lesson fixture');
        $this->get($exerciseUrl)->assertSeeInOrder(['Preparation lesson', 'Completed']);
        $this->get($examUrl)->assertSeeInOrder(['Preparation lesson', 'Completed']);
        $this->get(route('account.home'))->assertSeeInOrder(['Your next steps', 'Next lesson fixture']);
        $this->get($exerciseResult)->assertSeeInOrder(['Where to next?', 'Next lesson fixture']);
        $this->get($examResult)->assertSeeInOrder(['Where to next?', 'Preparation lesson'])->assertDontSee('Hidden preparation fixture');
        $this->actingAs($other)->get($exerciseUrl)->assertSee('Recommended review');
        $this->get($exerciseResult)->assertNotFound();
        $this->get($examResult)->assertNotFound();
    }

    private function category(): Category
    {
        $level = Level::forceCreate(['title' => 'Learning path', 'status' => 'published']);
        $unit = $level->units()->forceCreate(['title' => 'Unit', 'status' => 'published']);

        return $unit->categories()->create(['title' => 'Grammar']);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $role)->sole());

        return $user;
    }
}
