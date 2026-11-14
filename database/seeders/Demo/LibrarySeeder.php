<?php

namespace Database\Seeders\Demo;

use App\Actions\Exams\SaveExam;
use App\Actions\Resources\SaveResource;
use App\Actions\Videos\SaveVideo;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Resource;
use App\Models\Topic;
use App\Models\Video;
use App\Support\CurriculumStatus;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $resources = [
            ['Editing a school bulletin', 'Practise rewriting a report in the passive and check which facts belong in the lead.', 'worksheet', 'public', 'school-bulletin', 'The passive voice'],
            ['From a count to a claim', 'Guide de révision : comparer des chiffres et rédiger une conclusion prudente en anglais.', 'study-guide', 'public', 'evidence-guide', 'What the refill station changed'],
            ['A request someone can act on', 'Plan a formal request, make arrangements and use a peer-review checklist. افتح الملف للتدرّب على صياغة طلب رسمي باللغة الإنجليزية.', 'pdf', 'points', 'civic-writing', 'A formal request to a local association'],
            ['Find the right platform', 'Read a platform-change notice, confirm a step-free route and text a traveller the key details.', 'worksheet', 'registered', 'travel-card', 'A station notice after a platform change'],
        ];

        foreach ($resources as [$title, $description, $type, $access, $filename, $lessonTitle]) {
            $lesson = Lesson::where('title', $lessonTitle)->sole();
            $unit = $lesson->category->unit;
            $resource = Resource::where('title', $title)->first();
            if (! $resource) {
                $resource = app(SaveResource::class)->handle(new Resource, compact('title', 'description', 'type', 'access'), $this->pdf($filename), [
                    'level_ids' => [$unit->level_id],
                    'lesson_ids' => [$lesson->id],
                ]);
            } elseif ($resource->storedFile && ! Storage::disk($resource->storedFile->disk)->exists($resource->storedFile->path)) {
                Storage::disk($resource->storedFile->disk)->put($resource->storedFile->path, file_get_contents($this->pdf($filename)->getPathname()));
            }
            if ($resource->wasRecentlyCreated) {
                $resource->forceFill(['status' => CurriculumStatus::Published])->save();
            }
            Topic::where('title', $unit->title)->sole()->resources()->syncWithoutDetaching([$resource->id]);
        }

        $videos = [
            ['What’s the passive voice? · BBC Learning English', 'Compare the active and passive examples. Pause and identify the receiver before checking the verb form.', '7GSrQ4DX8gY', 'The passive voice'],
            ['English Class: Reported speech · BBC Learning English', 'Follow the reported-speech examples and note where the pronoun and tense change.', 'MQZpmmyvULw', 'Reported speech in a meeting summary'],
            ['How to ask for and give directions · BBC Learning English', 'Listen for the landmarks and turning points, then describe the route aloud in your own words.', 'hKLhepR2KY4', 'Checking directions politely'],
        ];
        foreach ($videos as [$title, $description, $source, $lessonTitle]) {
            $lesson = Lesson::where('title', $lessonTitle)->sole();
            $unit = $lesson->category->unit;
            $video = Video::where('title', $title)->first();
            if (! $video) {
                $video = app(SaveVideo::class)->handle(new Video, compact('title', 'description'), 'https://www.youtube.com/watch?v='.$source, [
                    'level_ids' => [$unit->level_id],
                    'lesson_ids' => [$lesson->id],
                ]);
                $video->forceFill(['status' => CurriculumStatus::Published])->save();
            }
            Topic::where('title', $unit->title)->sole()->videos()->syncWithoutDetaching([$video->id]);
            if ($source === '7GSrQ4DX8gY') {
                Topic::where('title', 'Passive voice · Demo')->sole()->videos()->syncWithoutDetaching([$video->id]);
            }
        }

        $unit = Level::where('title', '2nd Baccalaureate · Demo')->sole()->units()->where('title', 'Citizenship')->sole();
        $title = 'A library that stays open later · Practice paper';
        $exam = $unit->exams()->where('title', $title)->first();
        if (! $exam) {
            $exam = app(SaveExam::class)->handle(new Exam, [
                'unit_id' => $unit->id,
                'type' => Exam::DOCUMENT,
                'title' => $title,
                'instructions' => 'Allow 40 minutes for reading, language and writing. Compare your work with the marking guide after you finish.',
                'access' => 'registered',
            ], ['examFile' => $this->pdf('community-exam'), 'correctionFile' => $this->pdf('community-marking')]);
            $exam->forceFill(['status' => CurriculumStatus::Published])->save();
        } else {
            foreach (['examFile' => 'community-exam', 'correctionFile' => 'community-marking'] as $relation => $filename) {
                $file = $exam->{$relation};
                if ($file && ! Storage::disk($file->disk)->exists($file->path)) {
                    Storage::disk($file->disk)->put($file->path, file_get_contents($this->pdf($filename)->getPathname()));
                }
            }
        }
        Topic::where('title', $unit->title)->sole()->exams()->syncWithoutDetaching([$exam->id]);
    }

    private function pdf(string $name): UploadedFile
    {
        return new UploadedFile(__DIR__."/documents/{$name}.pdf", $name.'.pdf', 'application/pdf', null, true);
    }
}
