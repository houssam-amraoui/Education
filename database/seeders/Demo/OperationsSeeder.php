<?php

namespace Database\Seeders\Demo;

use App\Models\AccessRule;
use App\Models\Achievement;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\PlatformSetting;
use App\Models\PointCost;
use App\Models\PointRule;
use App\Models\Resource;
use App\Support\RewardEvent;
use App\Support\RewardTarget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DB::transaction(function (): void {
            foreach (['platform_name' => 'Atlas English Classroom', 'default_locale' => 'en', 'enabled_locales' => ['en', 'fr', 'ar']] as $name => $value) {
                PlatformSetting::firstOrCreate(['name' => $name], ['value' => $value]);
            }

            foreach ([
                ['Lesson complete', RewardEvent::LessonCompletion, 8, null, null, true],
                ['Practice score of 75% or higher', RewardEvent::ExerciseResult, 12, 75, null, true],
                ['Interactive exam submitted', RewardEvent::ExamCompletion, 15, null, null, true],
                ['Three consecutive study days', RewardEvent::StreakMilestone, 10, null, 3, true],
                ['Seven consecutive study days', RewardEvent::StreakMilestone, 20, null, 7, false],
            ] as [$name, $event, $points, $minimum, $days, $enabled]) {
                PointRule::firstOrCreate(compact('name'), compact('event', 'points', 'enabled') + [
                    'minimum_score_percent' => $minimum, 'streak_days' => $days, 'allow_retries' => false,
                ]);
            }

            foreach ([
                ['First step', 'Complete your first lesson.', RewardEvent::LessonCompletion, 1, null, 3],
                ['Steady learner', 'Complete five different lessons.', RewardEvent::LessonCompletion, 5, null, 8],
                ['Careful reviser', 'Score 75% or higher on two different exercises.', RewardEvent::ExerciseResult, 2, 75, 10],
                ['Exam ready', 'Submit your first interactive exam.', RewardEvent::ExamCompletion, 1, null, 5],
                ['Three-day rhythm', 'Study on three consecutive days.', RewardEvent::StreakMilestone, 3, null, 5],
            ] as [$name, $description, $event, $count, $minimum, $points]) {
                Achievement::firstOrCreate(compact('name'), compact('description', 'event') + [
                    'required_count' => $count, 'minimum_score_percent' => $minimum, 'reward_points' => $points, 'enabled' => true,
                ]);
            }

            $resource = Resource::where('title', 'A request someone can act on')->sole();
            PointCost::firstOrCreate(['target_type' => RewardTarget::Resource, 'target_id' => $resource->id], ['points' => 20, 'enabled' => true]);

            foreach ([
                ['resource', $resource->id, 'points', []],
                ['resource', Resource::where('title', 'Find the right platform')->sole()->id, 'registered', []],
                ['lesson', Lesson::where('title', 'A formal request to a local association')->sole()->id, 'level', [
                    'required_level_id' => Level::where('title', '2nd Baccalaureate · Demo')->sole()->id,
                ]],
                ['lesson', Lesson::where('title', 'An evidence-based proposal')->sole()->id, 'prerequisite', [
                    'required_lesson_id' => Lesson::where('title', 'What the refill station changed')->sole()->id,
                ]],
                ['exercise', Exercise::where('title', 'Editing a consultation summary')->sole()->id, 'premium', []],
            ] as [$type, $id, $requirement, $conditions]) {
                AccessRule::firstOrCreate(['target_type' => $type, 'target_id' => $id, 'requirement' => $requirement], $conditions);
            }
        });

        $this->call(LearnerActivitySeeder::class);
    }
}
