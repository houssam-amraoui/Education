<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $default = app()->getLocale();
        $saved = $request->routeIs('account.*') ? $request->user()?->locale : null;
        $locale = Locale::resolve($request->session()->get('locale', $saved ?? $default));

        app()->setLocale($locale);

        try {
            return $next($request);
        } finally {
            app()->setLocale($default);
        }
    }
}
