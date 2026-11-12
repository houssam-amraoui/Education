<?php

use App\Http\Middleware\StartPreviewSession;
use App\Support\AccountPages;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToPriorityList(StartSession::class, StartPreviewSession::class);
        $middleware->redirectGuestsTo(fn (Request $request): ?string => AccountPages::html($request) ? route('account.login.show') : null);
        $middleware->redirectUsersTo(fn (): string => route('account.home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->expectsJson()
                || ($request->routeIs('account.*', 'admin.accounts.*', 'admin.curriculum.*', 'admin.library.*') && ! AccountPages::html($request))
        );
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getStatusCode() === 403 && $request->routeIs('admin.accounts.*', 'admin.curriculum.*', 'admin.library.*') && AccountPages::html($request)) {
                return response(AccountPages::view('A01', 'restricted'), 403);
            }
            if ($exception->getStatusCode() === 403 && $request->routeIs('account.profile.*', 'account.preferences.*') && AccountPages::html($request)) {
                return response(AccountPages::view('S22', 'restricted'), 403);
            }
        });
    })->create();
