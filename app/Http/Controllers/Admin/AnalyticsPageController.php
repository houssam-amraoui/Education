<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsReportRequest;
use App\Models\AnalyticsEvent;
use App\Models\Conversation;
use App\Models\Role;
use App\Models\User;
use App\Queries\Analytics\AnalyticsActivity;
use App\Queries\Analytics\AnalyticsReports;
use App\Support\AccountPages;
use App\Support\ReportingPeriod;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AnalyticsPageController extends Controller
{
    public const SCREENS = [
        'overview' => 'A44',
        'acquisition' => 'A45',
        'engagement' => 'A46',
        'learning' => 'A47',
        'communication' => 'A48',
        'business' => 'A49',
    ];

    public function __invoke(
        AnalyticsReportRequest $request,
        AnalyticsReports $reports,
        AnalyticsActivity $activity,
        string $report = 'overview',
    ): View {
        $period = $request->period();
        $dates = ['from' => $period->start->toDateString(), 'through' => $period->end->subDay()->toDateString()];
        $links = [];
        foreach (self::SCREENS as $section => $screen) {
            $links[$screen] = ['url' => route('admin.analytics.show', ['report' => $section === 'overview' ? null : $section] + $dates)];
        }

        $metrics = $reports->forUser($request->user(), $period, $report);
        $series = match ($report) {
            'overview' => $metrics['engagement']['daily_active_users'],
            'engagement' => $metrics['daily_active_users'],
            'communication' => $metrics['daily_messages'],
            'business' => [],
            default => $activity->dailyActiveUsers($period),
        };

        $chart = [];
        $lastDay = $period->end->subDay();
        for ($day = max($period->start, $lastDay->subDays(6)); $day <= $lastDay; $day = $day->addDay()) {
            $chart[$day->toDateString()] = $series[$day->toDateString()] ?? 0;
        }

        if ($report === 'acquisition') {
            $week = ReportingPeriod::fromDates(array_key_first($chart), $dates['through']);
            $metrics['recent_registrations'] = $activity->events(AnalyticsEvent::REGISTRATION, $week)->count();
        }

        $student = null;
        if (in_array($report, ['overview', 'engagement', 'learning'], true) && Gate::allows('viewAny', User::class)) {
            $student = User::whereRelation('roles', 'key', Role::STUDENT)
                ->whereIn('id', $activity->studentActivity($period)->select('student_id'))->latest('id')->first();
        }

        $conversation = null;
        if (in_array($report, ['overview', 'communication'], true) && Gate::allows('manage', Conversation::class)) {
            $conversation = $activity->conversations($period)->latest('last_message_at')->latest('id')->first();
        }

        return AccountPages::view(self::SCREENS[$report], 'analytics', [
            'crumbs' => $report === 'overview' ? ['A01'] : ['A01', 'A44'],
            'links' => $links,
            'report' => $report,
            'dates' => $dates,
            'metrics' => $metrics,
            'chart' => $chart,
            'student' => $student && Gate::allows('viewStudentProgress', $student) ? $student : null,
            'conversation' => $conversation && Gate::allows('view', $conversation) ? $conversation : null,
        ], 'admin.analytics');
    }
}
