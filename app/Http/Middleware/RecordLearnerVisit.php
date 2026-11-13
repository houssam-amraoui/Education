<?php

namespace App\Http\Middleware;

use App\Actions\Analytics\RecordAnalyticsEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordLearnerVisit
{
    public function __construct(private RecordAnalyticsEvent $analytics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()
            && str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            $this->analytics->visit($request);
        }

        return $response;
    }
}
