<?php

namespace Database\Seeders\Demo;

use App\Actions\Analytics\RecordAnalyticsEvent;
use App\Actions\Auth\CreateStudentAccount;
use App\Actions\Auth\SaveSelectedLevels;
use App\Actions\Exams\StartExamAttempt;
use App\Actions\Exams\SubmitExamAttempt;
use App\Actions\Exercises\StartExerciseAttempt;
use App\Actions\Exercises\SubmitExerciseAttempt;
use App\Actions\Lessons\RecordLessonProgress;
use App\Actions\Messaging\AppendConversationMessage;
use App\Actions\Messaging\CreateConversation;
use App\Actions\Rewards\UnlockWithPoints;
use App\Models\AnalyticsEvent;
use App\Models\Entitlement;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Resource;
use App\Models\Role;
use App\Models\User;
use App\Models\Video;
use App\Support\Ability;
use App\Support\QuestionType;
use App\Support\RewardTarget;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class LearnerActivitySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $today = now()->utc()->toImmutable()->startOfDay();
        $teacher = $this->staff('demo.teacher@example.test', 'Salma El Idrissi', 'Tétouan', 'fr', Role::TEACHER);
        $this->staff('demo.admin@example.test', 'Youssef Amrani', 'Rabat', 'en', Role::ADMINISTRATOR);

        foreach (require __DIR__.'/learners.php' as $handle => $profile) {
            [$name, $city, $locale, $goal, $focus, $paths, $registered, $events] = $profile;
            $email = "demo.{$handle}@example.test";
            // Account creation and the complete history commit together. Existing accounts keep their history and permissions.
            DB::transaction(function () use ($today, $teacher, $name, $city, $locale, $goal, $focus, $paths, $registered, $events, $email): void {
                if (User::where('email', $email)->exists()) {
                    return;
                }
                $this->at($today->subDays($registered)->addHours(9), function () use ($email): void {
                    app(RecordAnalyticsEvent::class)->visit($this->request($email));
                });
                $student = $this->at($today->subDays($registered)->addHours(16), function () use ($email, $name, $city, $locale, $goal, $focus, $paths): User {
                    $student = app(CreateStudentAccount::class)->handle(['name' => $name, 'email' => $email, 'password' => 'password']);
                    $student->forceFill(['city' => $city, 'locale' => $locale, 'daily_study_goal_minutes' => $goal, 'learning_focus' => $focus])->save();
                    app(SaveSelectedLevels::class)->handle($student, Level::whereIn('title', $paths)->pluck('id')->all());

                    return $student;
                });
                foreach ($events as [$daysAgo, $hour, $action, $target, $details]) {
                    $this->at($today->subDays($daysAgo)->addHours($hour), function () use ($student, $teacher, $action, $target, $details): void {
                        $this->activity($student, $teacher, $action, $target, $details);
                    });
                }
            });
        }
    }

    private function staff(string $email, string $name, string $city, string $locale, string $role): User
    {
        return DB::transaction(function () use ($email, $name, $city, $locale, $role): User {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $user = User::factory()->create(compact('email', 'name', 'city', 'locale'));
                $user->roles()->attach(Role::where('key', $role)->sole());
                if ($role === Role::TEACHER) {
                    $support = Role::firstOrCreate(['key' => 'demo-learning-support'], [
                        'name' => 'Learning Support',
                        'description' => 'Replies to lesson questions from the demonstration cohort.',
                    ]);
                    if ($support->wasRecentlyCreated) {
                        $support->abilities()->create(['ability' => Ability::MANAGE_CONVERSATIONS]);
                    }
                    $user->roles()->attach($support);
                }
            }

            return $user;
        });
    }

    private function activity(User $student, User $teacher, string $action, string $title, array $details): void
    {
        if (in_array($action, ['complete', 'view'], true)) {
            $lesson = Lesson::where('title', $title)->sole();
            $progress = app(RecordLessonProgress::class);
            $progress->view($student, $lesson);
            if ($action === 'complete') {
                $this->at(now()->toImmutable()->addMinutes(12), fn () => $progress->complete($student, $lesson));
            }
        } elseif ($action === 'exercise' || $action === 'exam') {
            $this->attempt($student, $action, $title, $details);
        } elseif ($action === 'message') {
            [$subject, $message, $reply, $followUp] = $details;
            $source = $title === '' ? null : Lesson::where('title', $title)->sole();
            $conversation = app(CreateConversation::class)->handle($student, compact('subject', 'message'), $source ? 'lesson' : null, $source?->id);
            if ($reply !== null) {
                $messages = app(AppendConversationMessage::class);
                $this->at(now()->toImmutable()->addMinutes(40), fn () => $messages->reply($teacher, $conversation->id, ['reply' => $reply]));
                if ($followUp !== null) {
                    $this->at(now()->toImmutable()->addMinutes(55), fn () => $messages->followUp($student, $conversation->id, ['reply' => $followUp]));
                }
            }
        } elseif ($action === 'access') {
            $exercise = Exercise::where('title', $title)->sole();
            Entitlement::firstOrCreate([
                'student_id' => $student->id,
                'target_type' => $exercise->getMorphClass(),
                'target_id' => $exercise->id,
                'source' => 'premium',
            ], ['granted_at' => now()]);
        } elseif ($action === 'unlock') {
            $resource = Resource::where('title', $title)->sole();
            app(UnlockWithPoints::class)->handle($student, RewardTarget::Resource, $resource->id);
        } elseif ($action === 'resource' || $action === 'video') {
            $resource = $action === 'resource';
            $model = $resource ? Resource::class : Video::class;
            $record = $model::visibleTo($student)->where('title', $title)->sole();
            app(RecordAnalyticsEvent::class)->content($this->request($student->email, $student),
                $resource ? AnalyticsEvent::RESOURCE_OPEN : AnalyticsEvent::VIDEO_PLAYBACK, $record->id);
        }
    }

    private function attempt(User $student, string $action, string $title, array $details): void
    {
        [$minutes, $mistakes] = $details;
        if ($action === 'exercise') {
            $attempt = app(StartExerciseAttempt::class)->handle($student, Exercise::where('title', $title)->sole());
            $submit = app(SubmitExerciseAttempt::class);
        } else {
            $attempt = app(StartExamAttempt::class)->handle($student, Exam::where('title', $title)->sole());
            $submit = app(SubmitExamAttempt::class);
        }
        if ($minutes !== null) {
            $answers = [];
            foreach ($attempt->items as $item) {
                $answers[$item->id] = $mistakes[$item->position] ?? $this->correctResponse($item->type, $item->answer_key);
            }
            $this->at(now()->toImmutable()->addMinutes($minutes), fn () => $submit->handle($student, $attempt, $answers));
        }
    }

    private function correctResponse(QuestionType $type, array $key): mixed
    {
        return match ($type) {
            QuestionType::SingleChoice => $key['option_id'],
            QuestionType::MultipleSelection => $key['option_ids'],
            QuestionType::TrueFalse => $key['value'],
            QuestionType::Matching => array_column($key['pairs'], 'right_id', 'left_id'),
            QuestionType::FillInTheBlanks => array_map(fn ($slot) => $slot['accepted'][0], array_column($key['slots'], null, 'id')),
            QuestionType::ShortAnswer => $key['accepted'][0],
            QuestionType::Ordering => $key['item_ids'],
        };
    }

    private function request(string $browser, ?User $student = null): Request
    {
        $session = new Store('demo', new NullSessionHandler);
        $session->put('analytics.browser', 'education-demo:'.$browser);
        $request = Request::create('/');
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $student);

        return $request;
    }

    private function at(CarbonImmutable $time, callable $callback): mixed
    {
        $previous = Date::getTestNow();
        Date::setTestNow($time);
        try {
            return $callback();
        } finally {
            Date::setTestNow($previous);
        }
    }
}
