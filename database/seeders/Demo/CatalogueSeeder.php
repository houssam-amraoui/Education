<?php

namespace Database\Seeders\Demo;

use App\Actions\Questions\ManageQuestions;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Topic;
use App\Models\Unit;
use App\Support\CurriculumStatus;
use App\Support\QuestionDefinition;
use App\Support\QuestionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DB::transaction(function (): void {
            $published = ['status' => CurriculumStatus::Published];
            foreach (require __DIR__.'/curriculum.php' as $path => $definition) {
                $level = Level::firstOrCreate(['title' => $path], $published + ['description' => $definition['description']]);
                foreach ($definition['units'] as $title => $content) {
                    $unit = $level->units()->firstOrCreate(['title' => $title], $published + ['description' => $content['description']]);
                    $topic = Topic::firstOrCreate(['title' => $title], $published + ['description' => $content['description']]);
                    $previous = null;
                    foreach ($content['lessons'] as $entry) {
                        $lesson = $this->lesson($unit, $entry);
                        $topic->lessons()->syncWithoutDetaching([$lesson->id]);
                        if ($previous) {
                            $previous->nextLessons()->syncWithoutDetaching([$lesson->id]);
                        }
                        $previous = $lesson;
                    }
                }
            }

            $level = Level::where('title', '2nd Baccalaureate · Demo')->sole();
            $youth = $level->units()->where('title', 'Gifts of youth')->sole();
            $grammar = $youth->categories()->where('title', 'Grammar')->sole();
            $passive = $grammar->lessons()->where('title', 'The passive voice')->sole();
            $passive->prerequisiteLessons()->syncWithoutDetaching([$grammar->lessons()->where('title', 'Past participles')->sole()->id]);

            foreach (require __DIR__.'/assessments.php' as [$path, $unitTitle, $type, $title, $instructions, $prerequisites, $questions, $duration]) {
                $unit = Level::where('title', $path)->sole()->units()->where('title', $unitTitle)->sole();
                $this->assessment($unit, $type, $title, $instructions, $prerequisites, $questions, $duration);
            }

            $topic = Topic::firstOrCreate(['title' => 'Passive voice · Demo'], $published + [
                'description' => 'Choose the correct form of be and use past participles to report what happened.',
            ]);
            $topic->lessons()->syncWithoutDetaching($grammar->lessons()->whereIn('title', ['The passive voice', 'Past participles'])->pluck('id'));
            $topic->exercises()->syncWithoutDetaching($youth->exercises()->where('title', 'Passive voice practice')->pluck('id'));
            $topic->exams()->syncWithoutDetaching($youth->exams()->where('title', 'Gifts of youth · Grammar review')->pluck('id'));

            $citizenship = $level->units()->where('title', 'Citizenship')->sole();
            $this->lesson($citizenship, ['Writing', 'An application for a youth council place', 'Explain how a specific experience prepares you to represent others.', 'Describe one action and its result before making a claim about your skills. For example, “I asked 24 students which library hours they could use, then shared the results with the librarian” demonstrates consultation.', 'Write an opening paragraph explaining whose views you would represent and how you would collect them.'], CurriculumStatus::Draft);
            $this->lesson($citizenship, ['Writing', 'A printed letter to the community centre', 'Structure a paper request using a subject line and a signed closing.', 'Place the date near the top and write a clear subject below the address. Use a separate paragraph for your request and include a way for the centre to respond.', 'Draft a letter requesting permission to use the hall on a named date.'], CurriculumStatus::Archived);
        });
    }

    private function lesson(Unit $unit, array $entry, CurriculumStatus $status = CurriculumStatus::Published): Lesson
    {
        [$categoryName, $title, $objective, $teaching, $activity] = $entry;
        $category = $unit->categories()->firstOrCreate(['title' => $categoryName], ['status' => CurriculumStatus::Published]);
        $body = '<h2>Learning objective</h2><p>'.e($objective).'</p>'
            .'<h2>Explanation and example</h2><p>'.e($teaching).'</p>'
            .'<h2>Try it</h2><p>'.e($activity).'</p>';
        foreach ([5 => 'Repère en français', 6 => 'توضيح بالعربية'] as $index => $heading) {
            if (isset($entry[$index])) {
                $body .= '<h3>'.$heading.'</h3><p>'.e($entry[$index]).'</p>';
            }
        }

        return $category->lessons()->firstOrCreate(['title' => $title], ['status' => $status, 'body' => $body]);
    }

    private function assessment(Unit $unit, string $type, string $title, string $instructions, array $prerequisites, array $questions, ?int $duration): void
    {
        $attributes = ['status' => CurriculumStatus::Published, 'instructions' => $instructions];
        $owner = $type === 'exercise'
            ? $unit->exercises()->firstOrCreate(['title' => $title], $attributes)
            : $unit->exams()->firstOrCreate(['title' => $title, 'type' => Exam::INTERACTIVE], $attributes + [
                'access' => 'registered', 'duration_minutes' => $duration,
            ]);
        $lessonIds = Lesson::whereIn('title', $prerequisites)
            ->whereHas('category', fn ($category) => $category->where('unit_id', $unit->id))->pluck('id');
        $owner->prerequisiteLessons()->syncWithoutDetaching($lessonIds);
        $topic = Topic::where('title', $unit->title)->sole();
        ($owner instanceof Exercise ? $topic->exercises() : $topic->exams())->syncWithoutDetaching([$owner->id]);

        foreach ($questions as $entry) {
            $question = $this->question($entry);
            if (! $owner->questions()->where('prompt', $question['prompt'])->exists()) {
                app(ManageQuestions::class)->save($owner, $question);
            }
        }
    }

    private function question(array $entry): array
    {
        [$type, $prompt, $data, $key, $explanation, $points] = $entry;
        $items = fn (array $labels): array => array_map(
            fn ($id, $label) => ['id' => $id, 'label' => $label], array_keys($labels), $labels,
        );
        [$content, $answer] = match ($type) {
            QuestionType::SingleChoice => [['options' => $items($data)], ['option_id' => $key]],
            QuestionType::MultipleSelection => [['options' => $items($data)], ['option_ids' => $key]],
            QuestionType::TrueFalse => [[], ['value' => $key]],
            QuestionType::ShortAnswer => [[], ['accepted' => $key]],
            QuestionType::FillInTheBlanks => [['slots' => $items($data)], ['slots' => array_map(
                fn ($id, $accepted) => ['id' => $id, 'accepted' => $accepted], array_keys($key), $key,
            )]],
            QuestionType::Matching => [
                ['left' => $items($data[0]), 'right' => $items($data[1])],
                ['pairs' => array_map(fn ($left, $right) => ['left_id' => $left, 'right_id' => $right], array_keys($key), $key)],
            ],
            QuestionType::Ordering => [['items' => $items($data)], ['item_ids' => $key]],
        };
        $definition = compact('prompt', 'content', 'answer', 'explanation', 'points') + ['type' => $type->value];

        return Validator::make($definition, QuestionDefinition::rules($type->value))
            ->after(QuestionDefinition::validateReferences(...))->validate();
    }
}
