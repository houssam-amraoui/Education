<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Queries\VisibleQuestions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class QuestionUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_use_owner_scoped_routes_and_persist_browser_form_actions(): void
    {
        $unit = Level::create(['title' => 'Level'])->units()->create(['title' => 'Unit']);
        $exercise = $unit->exercises()->create(['title' => 'Exercise', 'instructions' => 'Answer.']);
        $exam = $unit->exams()->create(['title' => 'Interactive exam', 'type' => Exam::INTERACTIVE]);
        $document = $unit->exams()->create(['title' => 'Document exam', 'type' => Exam::DOCUMENT]);
        $exerciseRoute = 'admin.curriculum.exercises.questions.';
        $examRoute = 'admin.curriculum.exams.questions.';
        $list = route($exerciseRoute.'index', $exercise);
        $create = route($exerciseRoute.'create', $exercise);
        $browser = ['Accept' => 'text/html'];

        $this->withoutVite()->get($list)->assertRedirect(route('account.login.show'));
        $this->actingAs(User::factory()->create())->get($create)->assertForbidden();
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('key', Role::TEACHER)->sole());
        $this->actingAs($teacher);

        $this->get(route($exerciseRoute.'owners'))->assertOk()->assertSee($list);
        $this->get(route($examRoute.'owners'))->assertOk()->assertSee(route($examRoute.'index', $exam))->assertDontSee($document->title);
        $this->get(route($examRoute.'create', $document))->assertNotFound();
        $this->get($list)->assertOk()->assertSee('No questions yet')->assertSee($create);
        $this->get($create)->assertOk()->assertSee('Matching')->assertSee('Ordering / sequencing');

        $data = ['type' => 'true_false', 'prompt' => 'First question', 'points' => 2, 'answer' => ['value' => '0']];
        $this->from($create)->post(route($exerciseRoute.'store', $exercise), array_replace($data, ['points' => -1]), $browser)
            ->assertRedirect($create)->assertSessionHasErrors('points')->assertSessionHasInput('prompt', 'First question');
        $this->get($create)->assertOk()->assertSee('First question')->assertSee('data-live-errors');
        $this->post(route($exerciseRoute.'store', $exercise), $data, $browser)->assertRedirect($list)->assertStatus(303);
        $first = $exercise->questions()->sole();
        $this->assertSame(['value' => false], $first->answer);
        $this->post(route($examRoute.'store', $exam), $data, $browser)->assertRedirect(route($examRoute.'index', $exam));
        $this->get(route($examRoute.'edit', [$exam, $first]))->assertNotFound();

        $edit = route($exerciseRoute.'edit', [$exercise, $first]);
        $this->get($list)->assertOk()->assertSee($edit);
        $this->get($edit)->assertOk()->assertSee('First question')->assertSee('Save exercise question');
        $this->put(route($exerciseRoute.'update', [$exercise, $first]), array_replace($data, ['prompt' => 'Updated question']), $browser)
            ->assertRedirect($list);
        $this->post(route($exerciseRoute.'store', $exercise), array_replace($data, ['prompt' => 'Second question']), $browser)
            ->assertRedirect($list);
        $this->patch(route($exerciseRoute.'reorder', [$exercise, $first]), ['position' => 2], $browser)->assertRedirect($list);
        $this->get($list)->assertOk()->assertSeeInOrder(['Second question', 'Updated question']);
        $this->delete(route($exerciseRoute.'destroy', [$exercise, $first]), [], $browser)->assertRedirect($list);
        $this->assertDatabaseMissing('questions', ['id' => $first->id]);
        $this->assertSame(1, $exercise->questions()->sole()->position);

        $this->post(route($examRoute.'store', $exam), [
            'type' => 'fill_in_the_blanks', 'prompt' => 'English ___ spoken here.', 'points' => 2,
            'content' => ['slots' => [['id' => 'verb', 'label' => 'Verb']]],
            'answer' => ['slots' => [['id' => 'verb', 'accepted' => '["is","was"]']]],
        ], $browser)->assertRedirect(route($examRoute.'index', $exam));
        $this->assertSame(['slots' => [['id' => 'verb', 'accepted' => ['is', 'was']]]], $exam->questions()->ordered()->get()->last()->answer);
    }

    public function test_learner_component_renders_display_data_and_never_embeds_scoring_fields(): void
    {
        $level = Level::create(['title' => 'Level']);
        $level->forceFill(['status' => 'published'])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => 'published'])->save();
        $exercise = $unit->exercises()->create(['title' => 'Exercise', 'instructions' => 'Answer.']);
        $exercise->forceFill(['status' => 'published'])->save();
        $question = $exercise->questions()->make([
            'type' => 'single_choice', 'prompt' => 'Real prompt', 'points' => 2,
            'content' => ['options' => [['id' => 'a', 'label' => '<Option A>', 'correct' => true], ['id' => 'b', 'label' => 'Option B']]],
            'answer' => ['option_id' => 'private-key'], 'explanation' => 'Private explanation',
        ]);
        $question->forceFill(['position' => 1])->save();

        View::share('errors', new ViewErrorBag);
        $render = fn () => Blade::render('<x-questions.learner :questions="$questions" />', [
            'questions' => (new VisibleQuestions)->forExercise($exercise->id),
        ]);

        $html = $render();
        $this->assertStringContainsString('Real prompt', $html);
        $this->assertStringContainsString('&lt;Option A&gt;', $html);
        $this->assertStringContainsString('name="answers['.$question->id.']" value="a"', $html);
        $this->assertStringNotContainsString('private-key', $html);
        $this->assertStringNotContainsString('Private explanation', $html);
        $this->assertStringNotContainsString('correct', $html);

        $exercise->forceFill(['status' => 'draft'])->save();
        $this->assertStringContainsString('No questions yet', $render());
    }
}
