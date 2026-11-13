<?php

namespace App\Http\Requests\Admin;

use App\Support\ReportingPeriod;
use Illuminate\Foundation\Http\FormRequest;

class AnalyticsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('analytics.view') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('admin.analytics.show') && ! $this->hasAny(['from', 'through'])) {
            $today = now()->utc();
            $this->merge(['from' => $today->copy()->subDays(6)->toDateString(), 'through' => $today->toDateString()]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->routeIs('admin.analytics.show') ? $this->url() : parent::getRedirectUrl();
    }

    public function rules(): array
    {
        return ReportingPeriod::rules();
    }

    public function period(): ReportingPeriod
    {
        return ReportingPeriod::fromDates($this->validated('from'), $this->validated('through'));
    }
}
