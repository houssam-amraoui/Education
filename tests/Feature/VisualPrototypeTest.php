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
        $this->get('/admin/analytics/business')->assertSee('Business metrics aren’t available yet.');
    }

    public function test_prototype_has_no_write_routes(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            if (str_starts_with($route->getName() ?? '', 'prototype.')) {
                $this->assertSame(['GET', 'HEAD'], $route->methods());
            }
        }
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
