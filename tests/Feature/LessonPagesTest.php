<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Support\CurriculumStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_pages_save_with_feedback_and_scope_access(): void
    {
        $this->withHeader('Accept', 'text/html');
        $category = $this->category();
        $list = route('admin.curriculum.lessons.index', ['category_id' => $category->id]);
        $create = route('admin.curriculum.lessons.create', $category);
        $store = route('admin.curriculum.lessons.store', $category);
        $this->get($list)->assertRedirect(route('account.login.show'));
        $this->signInWithRole(Role::STUDENT);
        $this->get($create)->assertForbidden();
        $this->post($store, ['title' => 'Blocked'])->assertForbidden();

        $this->signInWithRole(Role::TEACHER);
        $this->get($list)->assertOk()->assertSee('No lessons yet')->assertSee($create);
        $this->from($create)->post($store, ['body' => '<p>Keep this text</p>'])
            ->assertRedirect($create)->assertSessionHasErrors('title');
        $this->get($create)->assertOk()->assertSee('Keep this text')->assertSee('The title field is required.');
        $this->post($store, ['title' => 'A lesson', 'body' => '<p>Keep this text</p>'])->assertStatus(303)->assertRedirect($list);

        $lesson = $category->lessons()->sole();
        $parameters = [$category, $lesson];
        $edit = route('admin.curriculum.lessons.edit', $parameters);
        $preview = route('admin.curriculum.lessons.preview', $parameters);
        $this->get($list)->assertOk()->assertSee('Lesson created.')->assertSee($edit)->assertSee('Draft');
        $this->get($edit)->assertOk()->assertSee('Keep this text')->assertSee($preview);
        $this->get($preview)->assertOk()->assertSee('Management preview')->assertSee('<p>Keep this text</p>', false);
        $this->get(route('admin.curriculum.lessons.edit', [$this->category(), $lesson]))->assertNotFound();
        $this->put(route('admin.curriculum.lessons.update', $parameters), ['title' => 'Edited', 'body' => '<p>New text</p>'])
            ->assertStatus(303)->assertRedirect($list);
        $this->patch(route('admin.curriculum.lessons.status', $parameters), ['status' => 'published', 'return_to' => 'editor'])
            ->assertStatus(303)->assertRedirect($edit);
        $this->get($edit)->assertOk()->assertSee('Lesson state saved.');
        $this->assertSame(CurriculumStatus::Published, $lesson->refresh()->status);

        $this->signInWithRole(Role::STUDENT);
        $this->get($preview)->assertForbidden();
        $this->get(route('admin.curriculum.lessons.relationships', $parameters))->assertForbidden();
    }

    public function test_public_pages_render_saved_lessons_and_only_link_to_visible_targets(): void
    {
        $category = $this->category();
        $parent = [$category->unit->level, $category->unit, $category];
        $lesson = $category->lessons()->create(['title' => 'First lesson', 'body' => '<p lang="ar" dir="rtl"><strong>مثال</strong></p><script>discard</script>']);
        $target = $category->lessons()->create(['title' => 'Next lesson']);
        $hiddenCategory = $this->category();
        $hiddenCategory->forceFill(['status' => CurriculumStatus::Archived])->save();
        $hidden = $hiddenCategory->lessons()->create(['title' => 'Unlisted lesson']);
        $list = route('curriculum.category', $parent);
        $detail = route('curriculum.lesson', [...$parent, $lesson]);
        $this->get($list)->assertOk()->assertSee('No lessons yet');
        $this->get($detail)->assertNotFound();

        foreach ([$lesson, $target, $hidden] as $record) {
            $record->forceFill(['status' => CurriculumStatus::Published])->save();
        }
        $lesson->nextLessons()->attach([$target->id, $hidden->id]);
        $this->get(route('curriculum.category', [...$parent, 'search' => 'First']))
            ->assertOk()->assertSee($detail)->assertDontSee('Next lesson');
        $targetUrl = route('curriculum.lesson', [...$parent, $target]);
        $this->get($detail)->assertOk()
            ->assertSee('<p lang="ar" dir="rtl"><strong>مثال</strong></p>', false)
            ->assertSee($targetUrl)->assertDontSee('Unlisted lesson')->assertDontSee('discard');
        $this->get($targetUrl)->assertOk()->assertSee('No lesson content yet');
        $category->unit->forceFill(['status' => CurriculumStatus::Draft])->save();
        $this->get($detail)->assertNotFound();
    }

    public function test_relationship_form_preserves_validation_and_can_clear_all_links(): void
    {
        $this->withHeader('Accept', 'text/html');
        $this->signInWithRole(Role::TEACHER);
        $category = $this->category();
        $source = $category->lessons()->create(['title' => 'Source lesson']);
        $target = $category->lessons()->create(['title' => 'Candidate lesson']);
        $view = route('admin.curriculum.lessons.relationships', [$category, $source]);
        $save = route('admin.curriculum.lessons.next-lessons.update', [$category, $source]);
        $this->get($view)->assertOk()->assertSee('Candidate lesson');
        $this->from($view)->put($save, ['next_lesson_ids' => [$target->id, $source->id]])
            ->assertRedirect($view)->assertSessionHasErrors('next_lesson_ids');
        $this->get($view)->assertOk()->assertSee('A lesson cannot link to itself.');
        $this->put($save, ['next_lesson_ids' => [$target->id]])->assertStatus(303)->assertRedirect($view);
        $this->assertSame([$target->id], $source->nextLessons()->pluck('lessons.id')->all());
        $this->put($save, ['next_lesson_ids' => ''])->assertStatus(303)->assertRedirect($view);
        $this->assertFalse($source->nextLessons()->exists());
    }

    public function test_relationship_selector_persists_assessments_and_only_links_visible_records(): void
    {
        $this->withHeader('Accept', 'text/html');
        $category = $this->category();
        $source = $category->lessons()->forceCreate(['title' => 'Source lesson', 'status' => 'published']);
        $exercise = $category->unit->exercises()->forceCreate([
            'title' => 'Lesson practice', 'instructions' => 'Practise.', 'status' => 'published',
        ]);
        $exam = $category->unit->exams()->forceCreate([
            'title' => 'Restricted assessment', 'type' => Exam::INTERACTIVE, 'status' => 'published', 'access' => 'registered',
        ]);
        $editor = route('admin.curriculum.lessons.relationships', [$category, $source]);
        $save = route('admin.curriculum.lessons.next-lessons.update', [$category, $source]);
        $detail = route('curriculum.lesson', [$category->unit->level, $category->unit, $category, $source]);
        $exerciseUrl = route('curriculum.exercises.instructions', $exercise);
        $examUrl = route('curriculum.exams.instructions', $exam);
        $links = ['next_lesson_ids' => '', 'exercise_ids' => [$exercise->id], 'exam_ids' => [$exam->id]];

        $this->signInWithRole(Role::STUDENT);
        $this->get($editor)->assertForbidden();
        $this->put($save, $links)->assertForbidden();
        $this->signInWithRole(Role::TEACHER);
        $this->get($editor)->assertOk()->assertSee('Lesson practice')->assertSee('Restricted assessment');
        $this->from($editor)->put($save, array_replace($links, ['exam_ids' => [99999]]))
            ->assertRedirect($editor)->assertSessionHasErrors('exam_ids')->assertSessionHasInput('exercise_ids', [$exercise->id]);
        $this->get($editor)->assertOk()->assertSee('href="#exam_ids"', false);
        $this->assertFalse($source->relatedExercises()->exists());
        $this->put($save, $links)->assertStatus(303)->assertRedirect($editor);
        $this->get($editor)->assertOk()->assertSee('Relationships saved.')
            ->assertSee('name="exercise_ids[]" value="'.$exercise->id.'"', false);
        auth()->logout();
        $this->get($detail)->assertOk()->assertSee($exerciseUrl)->assertDontSee($examUrl)->assertDontSee($exam->title);
        $this->get($exerciseUrl)->assertOk();
        $this->signInWithRole(Role::STUDENT);
        $this->get($detail)->assertOk()->assertSee($examUrl);
        $this->get($examUrl)->assertOk();

        $this->signInWithRole(Role::TEACHER);
        $this->put($save, ['next_lesson_ids' => '', 'exercise_ids' => '', 'exam_ids' => ''])->assertStatus(303);
        $this->assertFalse($source->relatedExercises()->exists());
        $this->assertFalse($source->relatedExams()->exists());
        $this->get($detail)->assertOk()->assertDontSee($exerciseUrl);
    }

    private function category(): Category
    {
        $level = Level::create(['title' => 'Learning path']);
        $level->forceFill(['status' => CurriculumStatus::Published])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => CurriculumStatus::Published])->save();

        return $unit->categories()->create(['title' => 'Grammar']);
    }

    private function signInWithRole(string $key): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('key', $key)->sole());
        $this->actingAs($user);
    }
}
