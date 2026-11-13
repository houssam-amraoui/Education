<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Level;
use App\Models\Role;
use App\Models\Topic;
use App\Models\User;
use App\Support\CurriculumStatus;
use App\Support\QuestionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemonstrationSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DB::transaction(function (): void {
            $published = ['status' => CurriculumStatus::Published];

            $level = Level::firstOrCreate(['title' => '2nd Baccalaureate · Demo'], $published + [
                'description' => 'Study and practise English grammar for 2nd Baccalaureate.',
            ]);
            $unit = $level->units()->firstOrCreate(['title' => 'Gifts of youth'], $published);
            $category = $unit->categories()->firstOrCreate(['title' => 'Grammar'], $published);

            $preparation = $category->lessons()->firstOrCreate(['title' => 'Past participles'], $published + [
                'body' => '<p>Regular verbs usually add -ed: organize → organized. Some verbs are irregular: write → written, build → built.</p>',
            ]);
            $lesson = $category->lessons()->firstOrCreate(['title' => 'The passive voice'], $published + [
                'body' => '<p>Use the passive when the action or its receiver matters most. Use a form of be followed by the past participle.</p><p>Active: Students organized the event. Passive: The event was organized by students.</p>',
            ]);
            $preparation->nextLessons()->syncWithoutDetaching([$lesson->id]);
            $lesson->prerequisiteLessons()->syncWithoutDetaching([$preparation->id]);

            $exercise = $unit->exercises()->firstOrCreate(['title' => 'Passive voice practice'], $published + [
                'instructions' => 'Choose the passive version of the sentence.',
            ]);
            $exercise->prerequisiteLessons()->syncWithoutDetaching([$lesson->id]);
            $exercise->questions()->firstOrCreate(['position' => 1], [
                'type' => QuestionType::SingleChoice,
                'prompt' => 'Volunteers built the library. Which sentence keeps the meaning and uses the passive?',
                'content' => ['options' => [
                    ['id' => 'a', 'label' => 'The library was built by volunteers.'],
                    ['id' => 'b', 'label' => 'The library is built by volunteers.'],
                ]],
                'answer' => ['option_id' => 'a'],
                'points' => 1,
                'explanation' => 'Use was + built to keep the original past tense.',
            ]);

            $exam = $unit->exams()->firstOrCreate([
                'title' => 'Gifts of youth · Grammar review',
                'type' => Exam::INTERACTIVE,
            ], $published + [
                'access' => 'registered',
                'instructions' => 'Decide whether the sentence uses the passive voice.',
            ]);
            $exam->prerequisiteLessons()->syncWithoutDetaching([$lesson->id]);
            $exam->questions()->firstOrCreate(['position' => 1], [
                'type' => QuestionType::TrueFalse,
                'prompt' => '“The invitations were sent yesterday” uses the passive voice.',
                'content' => [],
                'answer' => ['value' => true],
                'points' => 1,
                'explanation' => 'Were + sent is the past passive form.',
            ]);

            $topic = Topic::firstOrCreate(['title' => 'Passive voice · Demo'], $published);
            $topic->lessons()->syncWithoutDetaching([$preparation->id, $lesson->id]);
            $topic->exercises()->syncWithoutDetaching([$exercise->id]);
            $topic->exams()->syncWithoutDetaching([$exam->id]);

            $email = 'demo.student@example.test';
            if (! User::where('email', $email)->exists()) {
                $student = User::factory()->create(['name' => 'Demo Student', 'email' => $email]);
                $student->roles()->attach(Role::where('key', Role::STUDENT)->sole());
                $student->selectedLevels()->attach($level);
            }
        });
    }
}
