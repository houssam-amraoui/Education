<?php

namespace App\Actions\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class RecordAnalyticsEvent
{
    public function visit(Request $request): void
    {
        $this->record(AnalyticsEvent::VISIT, $this->browser($request));
    }

    public function registration(User $student): void
    {
        $this->record(AnalyticsEvent::REGISTRATION, 'student:'.$student->id, $student);
    }

    public function lessonOpen(User $student, int $lessonId, CarbonImmutable $viewedAt): void
    {
        $this->record(AnalyticsEvent::LESSON_OPEN, 'student:'.$student->id, $student, $lessonId, $viewedAt);
    }

    public function content(Request $request, string $event, int $targetId): void
    {
        $user = $request->user();
        $student = $user && Gate::forUser($user)->allows('manageLearningProfile', $user) ? $user : null;
        $actor = $student ? 'student:'.$student->id : $this->browser($request);

        $this->record($event, $actor, $student, $targetId);
    }

    private function browser(Request $request): string
    {
        return 'browser:'.$request->session()->remember('analytics.browser', fn () => (string) Str::uuid());
    }

    private function record(string $event, string $actor, ?User $student = null, int $targetId = 0, ?CarbonImmutable $now = null): void
    {
        $now ??= now()->utc()->toImmutable();

        AnalyticsEvent::upsert([[
            'event' => $event,
            'actor_key' => hash('sha256', $actor),
            'student_id' => $student?->id,
            'target_id' => $targetId,
            'event_day' => $now->toDateString(),
            'occurred_at' => $now->toDateTimeString(),
        ]], ['event', 'actor_key', 'target_id', 'event_day'], ['event_day']);
    }
}
