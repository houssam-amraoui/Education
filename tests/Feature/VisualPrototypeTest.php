<?php

namespace Tests\Feature;

use App\Support\Prototype;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VisualPrototypeTest extends TestCase
{
    public static function screens(): iterable
    {
        foreach (Prototype::screens() as $screen) {
            yield $screen['id'].' '.$screen['title'] => [$screen['id'], $screen['path']];
        }
    }

    #[DataProvider('screens')]
    public function test_screen_renders_without_a_database(string $id, string $path): void
    {
        // Any accidental query or database session use must fail this smoke test.
        config(['database.default' => 'prototype-database-is-forbidden', 'session.driver' => 'database']);

        $this->get($path)
            ->assertOk()
            ->assertSee('Visual prototype')
            ->assertSee($id.' · Visual validation')
            ->assertSee('id="main"', false);
    }

    public function test_lesson_attaches_visible_context_to_teacher_composition(): void
    {
        $this->get('/lessons/passive-voice')->assertSee('/messages/new?context=passive-voice', false);
        $this->get('/messages/new?context=passive-voice')
            ->assertOk()->assertSee('Attached learning context')->assertSee('The passive voice');
        $this->get('/messages/new')->assertDontSee('Attached learning context');
    }

    public function test_document_retains_its_source_and_editor_supports_both_modes(): void
    {
        $this->get('/documents/study-guide?source=exam')->assertSee('Back to exam')->assertSee('Sample exam paper');
        $this->get('/documents/study-guide?source=correction')->assertSee('Sample correction');
        $this->get('/admin/lessons/editor')->assertSee('Create lesson');
        $this->get('/admin/lessons/editor?mode=edit')->assertSee('Edit lesson')->assertSee('The passive voice');
    }

    public function test_sample_results_and_unavailable_business_metrics_are_explicit(): void
    {
        $this->get('/exercises/passive-voice/results')->assertSee('Fixed sample result');
        $this->get(route(Prototype::routeName('A49')))->assertSee('Business metrics aren’t available yet.');
    }

    public function test_next_learning_previews_show_fixture_reasons_and_an_empty_state(): void
    {
        foreach ([
            'S01' => 'A short exercise to reinforce today’s lesson.',
            'G07' => 'The passive in different tenses builds on the sentence structure in this lesson.',
            'S05' => 'A quick review of past participles could help.',
            'S08' => 'A quick review of past participles could help.',
        ] as $screen => $reason) {
            $this->get(Prototype::url($screen))
                ->assertOk()
                ->assertSee($reason)
                ->assertDontSee('No recommendations right now');

            $this->get(Prototype::url($screen, ['recommendations' => 'empty']))
                ->assertOk()
                ->assertSee('No recommendations right now')
                ->assertSee('Browse units')
                ->assertDontSee($reason);
        }
    }

    public function test_exam_catalogue_and_document_links_use_distinct_sample_files(): void
    {
        $this->get('/exams')->assertSee('/exams/national-document', false)->assertSee('/exams/unit-one', false);
        $this->get('/exams/national-document')
            ->assertSee('/documents/study-guide?source=exam', false)
            ->assertSee('/documents/study-guide?source=correction', false);

        $this->get('/documents/study-guide?source=exam')
            ->assertSee('/prototype/exam-paper.txt')->assertDontSee('/prototype/exam-correction.txt');
        $this->get('/documents/study-guide?source=correction')
            ->assertSee('/prototype/exam-correction.txt')->assertDontSee('/prototype/exam-paper.txt');
        $this->get('/documents/study-guide')->assertSee('/prototype/study-guide.txt');
    }

    public function test_interactive_exam_previews_timed_and_untimed_attempts(): void
    {
        $exam = '/exams/unit-one';

        $this->get($exam)->assertSee('30 min')->assertSee('Preview untimed exam');
        $this->get($exam.'?timing=untimed')->assertSee('No time limit')->assertSee($exam.'/attempt?timing=untimed', false);
        $this->get($exam.'/attempt')->assertSee('30:00 · Static timer preview');
        $this->get($exam.'/attempt?timing=untimed')
            ->assertSee('Untimed · No time limit')
            ->assertDontSee('Static timer preview')
            ->assertSee($exam.'/results?timing=untimed', false);
        $this->get($exam.'/attempt?timing[]=untimed')->assertSee('Static timer preview');
    }

    public function test_exam_history_only_links_to_unit_one_exam_results(): void
    {
        $exam = '/exams/unit-one';

        $this->get($exam.'/history')
            ->assertSeeInOrder(['Attempt 2', '15 / 20', 'Attempt 1', '10 / 20'])
            ->assertSee($exam.'/results?attempt=1', false)
            ->assertSee($exam.'/results?attempt=2', false)
            ->assertDontSee('aria-label="Review Passive voice practice"', false)
            ->assertDontSee('aria-label="Review Grammar review"', false);

        $this->get($exam.'/results')->assertSee('75% · Sample score')->assertSee('The school is built.');
        $this->get($exam.'/results?attempt=1&timing=untimed')
            ->assertSee('50% · Sample score')
            ->assertSee('They built the school.')
            ->assertSee('The school was built.')
            ->assertSee($exam.'/attempt?timing=untimed', false)
            ->assertSee($exam.'/history?timing=untimed', false);

        $this->get($exam.'/results?attempt=99')->assertNotFound();
        $this->get($exam.'/results?attempt[]=1')->assertNotFound();
    }

    public function test_prototype_has_no_write_routes(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            if (str_starts_with($route->getName() ?? '', 'prototype.')) {
                $this->assertSame(['GET', 'HEAD'], $route->methods());
            }
        }
    }

    public function test_access_previews_show_the_requested_destination_and_its_recovery_action(): void
    {
        $exercise = '/exercises/passive-voice';
        $exam = '/exams/unit-one';

        foreach ([
            ['exercise-attempt', $exercise, 'Passive voice practice', 'Sign-in required', '/login?return=exercise'],
            ['unit-exam', $exam, 'Unit 1 · Practice exam', 'Learning level required', '/learning-goals'],
            ['passive-voice-practice', $exercise, 'Passive voice practice', 'Prerequisite required', '/lessons/passive-voice'],
            ['grammar-worksheet', '/resources', 'Grammar revision worksheet', '100 points required', '/rewards/unlock'],
            ['revision-pack', '/resources', 'Complete revision pack', 'Premium content', null],
        ] as [$destination, $source, $title, $requirement, $action]) {
            $path = '/access?destination='.$destination;
            $this->get($source)->assertSee('href="'.url($path).'"', false);
            $this->get('/access')->assertSee('href="'.url($path).'"', false);

            $response = $this->get($path)
                ->assertOk()
                ->assertSeeInOrder([$title, $requirement])
                ->assertSee('href="'.url($source).'"', false)
                ->assertDontSee('Explore the sample states below.');

            if ($action !== null) {
                $response->assertSee('class="button button-primary"', false)
                    ->assertSee('href="'.url($action).'"', false);
            } else {
                $response->assertDontSee('class="button button-primary"', false);
            }
        }

        $this->get('/access?destination=unknown')->assertNotFound();
        $this->get('/access?destination[]=unit-exam')->assertNotFound();
    }

    public function test_exercise_history_links_to_distinct_sample_corrections(): void
    {
        $exercise = '/exercises/passive-voice';

        $this->get($exercise.'/history')
            ->assertOk()
            ->assertSeeInOrder(['Attempt 2', '16 / 20', 'Attempt 1', '12 / 20'])
            ->assertSee($exercise.'/results?attempt=2', false)
            ->assertSee($exercise.'/results?attempt=1', false)
            ->assertDontSee('aria-label="Review Unit 1 · Practice exam"', false)
            ->assertDontSee('aria-label="Review Grammar review"', false);

        $this->get($exercise.'/results')
            ->assertOk()
            ->assertSee('80% · Sample score')
            ->assertSee('The school is built.');

        $this->get($exercise.'/results?attempt=1')
            ->assertOk()
            ->assertSee('60% · Sample score')
            ->assertSee('They built the school.')
            ->assertSee('The school was built.')
            ->assertSee($exercise.'/attempt', false);

        $this->get($exercise.'/results?attempt=99')->assertNotFound();
        $this->get($exercise.'/results?attempt[]=1')->assertNotFound();
    }

    public function test_login_preview_uses_known_return_destinations_and_defaults_to_the_dashboard(): void
    {
        $this->get('/exercises/passive-voice')->assertSee('/login?return=exercise', false);
        $this->get('/exams/unit-one')->assertSee('/login?return=exam', false);

        $this->get('/login?return=exercise')->assertSee('/exercises/passive-voice/attempt', false);
        $this->get('/login?return=exam')->assertSee('/exams/unit-one/attempt', false);

        foreach (['/login', '/login?return=unknown', '/login?return=https%3A%2F%2Fexample.test', '/login?return[]=exam'] as $url) {
            $this->get($url)->assertOk()->assertSee('Explore sample dashboard');
        }
    }

    public function test_component_fixture_paginates_sample_lessons_without_a_database(): void
    {
        config(['database.default' => 'prototype-database-is-forbidden', 'session.driver' => 'database']);

        $this->get(route('prototype.primitives'))
            ->assertOk()
            ->assertSee('Showing 1–3 of 7')
            ->assertSee('The passive voice')
            ->assertDontSee('Gifts of youth');

        $this->get(route('prototype.primitives', ['page' => 2]))
            ->assertOk()
            ->assertSee('Showing 4–6 of 7')
            ->assertSee('Gifts of youth')
            ->assertDontSee('The passive voice');

        $this->get(route('prototype.primitives', ['page' => 99]))
            ->assertOk()
            ->assertSee('Showing 7–7 of 7')
            ->assertSee('Expressing agreement');
    }

    public function test_student_navigation_is_retained_on_shared_learning_pages(): void
    {
        $this->get('/dashboard')->assertSee('/lessons/passive-voice?preview=student', false);
        $this->get('/lessons/passive-voice?preview=student')
            ->assertSee('Your learning space')->assertSee('Dashboard');
        $this->get('/lessons/passive-voice')->assertSee('Explore English Atlas');
    }
}
