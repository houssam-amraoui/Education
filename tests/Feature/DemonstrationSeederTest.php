<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Role;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\DemonstrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemonstrationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_provides_a_repeatable_learning_path(): void
    {
        $this->seed();
        $this->seed();

        $student = User::where('email', 'demo.student@example.test')->sole();
        $this->assertTrue(Hash::check('password', $student->password));
        $this->assertSame([Role::STUDENT], $student->roles()->pluck('key')->all());

        $level = Level::visibleToPublic()->sole();
        $this->assertTrue($student->selectedLevels()->sole()->is($level));
        $unit = $level->units()->visibleToPublic()->sole();
        $category = $unit->categories()->visibleToPublic()->sole();
        $lesson = $category->lessons()->visibleToPublic()->where('title', 'The passive voice')->sole();
        $preparation = $lesson->prerequisiteLessons()->sole();
        $this->assertTrue($preparation->nextLessons()->sole()->is($lesson));

        $exercise = $unit->exercises()->visibleToPublic()->sole();
        $exam = $unit->exams()->visibleTo($student)->sole();
        $this->assertTrue($exercise->prerequisiteLessons()->sole()->is($lesson));
        $this->assertTrue($exam->prerequisiteLessons()->sole()->is($lesson));
        $this->assertSame(['option_id' => 'a'], $exercise->questions()->sole()->answer);
        $this->assertSame(['value' => true], $exam->questions()->sole()->answer);

        $topic = Topic::published()->sole();
        $this->assertCount(2, $topic->lessons);
        $this->assertTrue($topic->exercises()->sole()->is($exercise));
        $this->assertTrue($topic->exams()->sole()->is($exam));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('lessons', 2);
        $this->assertDatabaseCount('questions', 2);
    }

    public function test_default_and_explicit_demo_seeds_skip_production(): void
    {
        $this->app->instance('env', 'production');

        try {
            $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
            $this->artisan('db:seed', ['--class' => DemonstrationSeeder::class, '--force' => true])->assertSuccessful();
        } finally {
            $this->app->instance('env', 'testing');
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('levels', 0);
    }
}
