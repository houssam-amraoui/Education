<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LearnerQuestionPagesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('assessments')]
    public function test_browser_attempt_renders_owned_snapshots_and_shows_the_saved_outcome(string $resource): void
    {
        $this->withoutVite();
        $exam = $resource === 'exams';
        $assessment = $this->assessment($exam);
        $prefix = 'curriculum.'.$resource;
        $instructions = route($prefix.'.instructions', $assessment);
        $start = route($prefix.'.attempts.store', $assessment);
        $headers = ['Accept' => 'text/html'];

        $this->get($instructions)->assertOk()->assertSee('This assessment has no questions yet.');
        $question = $assessment->questions()->forceCreate([
            'type' => 'single_choice', 'prompt' => 'Saved question', 'position' => 1, 'points' => 3,
            'content' => ['options' => [
                ['id' => 'one', 'label' => 'First option', 'feedback' => 'Private embedded value'],
                ['id' => 'two', 'label' => 'Second option'],
            ]],
            'answer' => ['option_id' => 'two'], 'explanation' => 'Private correction explanation',
        ]);
        $this->get($instructions)->assertOk()->assertSee('Sign in with a student account');
        $this->get(route('account.login.show', ['return_to' => '/learn/'.$resource.'/'.$assessment->id]))
            ->assertSessionHas('url.intended', '/learn/'.$resource.'/'.$assessment->id);
        $this->post($start, [], $headers)->assertRedirect(route('account.login.show'));
        $this->actingAs(User::factory()->create())->post($start, [], $headers)->assertForbidden();

        $student = $this->student();
        $this->actingAs($student)->get($instructions)->assertOk()->assertSee('Start '.($exam ? 'exam' : 'exercise'));
        $started = $this->post($start, [], $headers)->assertStatus(303);
        $attempt = $assessment->attempts()->sole();
        $item = $attempt->items()->sole();
        $show = route($prefix.'.attempts.show', [$assessment, $attempt]);
        $submit = route($prefix.'.attempts.submit', [$assessment, $attempt]);
        $started->assertRedirect($show);
        $question->update(['prompt' => 'Edited after start', 'points' => 10]);
        $this->get($show, $headers)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Saved question')->assertSee('Second option')
            ->assertSee('data-learner-question="'.$item->id.'"', false)
            ->assertSee($instructions)->assertSee($submit)
            ->assertDontSee('Edited after start')->assertDontSee('Private embedded value')
            ->assertDontSee('Private correction explanation')->assertDontSee('answer_key');
        if ($exam) {
            $this->get($show, $headers)->assertSee('data-attempt-deadline');
        }

        $this->actingAs($this->student())->get($show, $headers)->assertNotFound();
        $this->actingAs($student)->get(route($prefix.'.attempts.show', [$this->assessment($exam), $attempt]), $headers)->assertNotFound();
        $this->postJson($submit, ['answers' => [$item->id => 'foreign']])
            ->assertUnprocessable()->assertJsonValidationErrors('answers.'.$item->id);
        $this->postJson($submit, ['answers' => [$item->id => 'two']])
            ->assertOk()->assertJsonPath('awarded_points', 3);
        $this->get($show, $headers)->assertOk()->assertSee('Completed attempt')->assertSee('Your score')
            ->assertDontSee('data-learner-attempt')->assertDontSee('Private correction explanation');
        $assessment->forceFill(['status' => 'draft'])->save();
        $this->get($show, $headers)->assertNotFound();
    }

    public function test_expired_exam_displays_the_server_outcome(): void
    {
        $this->withoutVite();
        $exam = $this->assessment(true);
        $exam->questions()->forceCreate([
            'type' => 'true_false', 'prompt' => 'A statement', 'position' => 1, 'points' => 1,
            'content' => [], 'answer' => ['value' => true],
        ]);
        $this->actingAs($this->student());
        $attempt = $this->postJson(route('curriculum.exams.attempts.store', $exam))->assertCreated()->json('id');
        $this->travel(2)->minutes();
        $this->postJson(route('curriculum.exams.attempts.submit', [$exam, $attempt]), ['answers' => []])
            ->assertOk()->assertJsonPath('status', 'expired');
        $this->get(route('curriculum.exams.attempts.show', [$exam, $attempt]), ['Accept' => 'text/html'])
            ->assertOk()->assertSee('This exam attempt has expired.')->assertDontSee('A statement');
    }

    public static function assessments(): array
    {
        return ['exercise' => ['exercises'], 'exam' => ['exams']];
    }

    private function assessment(bool $exam): Exercise|Exam
    {
        $level = Level::create(['title' => 'Level']);
        $level->forceFill(['status' => 'published'])->save();
        $unit = $level->units()->create(['title' => 'Unit']);
        $unit->forceFill(['status' => 'published'])->save();
        $assessment = $exam
            ? $unit->exams()->create(['title' => 'Exam', 'type' => Exam::INTERACTIVE, 'duration_minutes' => 1])
            : $unit->exercises()->create(['title' => 'Exercise', 'instructions' => 'Answer the questions.']);
        $assessment->forceFill(['status' => 'published'])->save();

        return $assessment;
    }

    private function student(): User
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::where('key', Role::STUDENT)->sole());

        return $student;
    }
}
