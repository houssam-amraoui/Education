<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsReportRequest;
use App\Queries\Analytics\AnalyticsReports;
use Illuminate\Http\JsonResponse;

class AnalyticsReportController extends Controller
{
    public function __invoke(AnalyticsReportRequest $request, AnalyticsReports $reports, string $report = 'overview'): JsonResponse
    {
        $period = $request->period();

        return response()->json([
            'period' => ['from' => $period->start->toDateString(), 'through' => $period->end->subDay()->toDateString()],
            'metrics' => $reports->forUser($request->user(), $period, $report),
        ]);
    }
}
