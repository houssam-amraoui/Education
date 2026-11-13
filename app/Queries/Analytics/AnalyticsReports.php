<?php

namespace App\Queries\Analytics;

use App\Models\User;
use App\Support\ReportingPeriod;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class AnalyticsReports
{
    public const REPORTS = ['overview', 'acquisition', 'engagement', 'learning', 'communication', 'business'];

    public function __construct(
        private Acquisition $acquisition,
        private Engagement $engagement,
        private Learning $learning,
        private Communication $communication,
    ) {}

    public function forUser(User $user, ReportingPeriod $period, string $report): array
    {
        Gate::forUser($user)->authorize('analytics.view');

        return $report === 'overview'
            ? array_combine(array_slice(self::REPORTS, 1), array_map(
                fn (string $section) => $this->section($period, $section),
                array_slice(self::REPORTS, 1),
            ))
            : $this->section($period, $report);
    }

    private function section(ReportingPeriod $period, string $report): array
    {
        return match ($report) {
            'acquisition' => $this->acquisition->forPeriod($period),
            'engagement' => $this->engagement->forPeriod($period),
            'learning' => $this->learning->forPeriod($period),
            'communication' => $this->communication->forPeriod($period),
            'business' => [
                'available' => false,
                'reason' => 'Commercial event sources are not configured.',
                'paid_content_conversions' => null,
                'teacher_service_leads' => null,
                'subscription_conversions' => null,
                'advertising_engagement' => null,
                'missing_sources' => [
                    'Paid-content conversions' => 'Verified paid fulfillment events are not configured.',
                    'Teacher-service leads' => 'Teacher-service lead capture is not configured.',
                    'Subscription conversions' => 'Verified subscription activation events are not configured.',
                    'Advertising engagement' => 'Verified advertising engagement events are not configured.',
                ],
            ],
            default => throw new InvalidArgumentException('Unknown analytics report.'),
        };
    }
}
