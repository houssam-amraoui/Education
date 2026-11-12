<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\AccountPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class SessionController extends Controller
{
    public function store(LoginRequest $request): Response|RedirectResponse
    {
        $credentials = $request->validated();
        $key = 'login:'.hash('sha256', Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            $message = __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]);
            if (AccountPages::html($request)) {
                throw ValidationException::withMessages(['email' => $message]);
            }

            throw new TooManyRequestsHttpException($seconds, $message);
        }

        RateLimiter::hit($key, 60);

        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        if (AccountPages::html($request)) {
            return redirect()->to(
                AccountPages::destination($request, $request->session()->pull('url.intended')) ?? route('account.home'),
                303
            );
        }

        return response()->noContent();
    }

    public function destroy(Request $request): Response|RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (AccountPages::html($request)) {
            return redirect()->route('account.login.show', status: 303)->with('status', 'You have signed out.');
        }

        return response()->noContent();
    }
}
