<?php

namespace App\Queries\Analytics;

use App\Models\AnalyticsEvent;
use App\Support\ReportingPeriod;

class Acquisition
{
    public function __construct(private AnalyticsActivity $activity) {}

    public function forPeriod(ReportingPeriod $period): array
    {
        return [
            'visitors' => $this->activity->events(AnalyticsEvent::VISIT, $period)->distinct()->count('actor_key'),
            'registrations' => $this->activity->events(AnalyticsEvent::REGISTRATION, $period)->count(),
            'active_students' => $this->activity->studentActivity($period)->distinct()->count('student_id'),
        ];
    }
}
