<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AccountLocaleRequest;
use App\Support\AccountPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountPageController extends Controller
{
    public function register(): View
    {
        return AccountPages::view('G21', 'auth');
    }

    public function login(): View
    {
        return AccountPages::view('G22', 'auth');
    }

    public function home(): View
    {
        return AccountPages::view('S01', 'home');
    }

    public function locale(AccountLocaleRequest $request): RedirectResponse
    {
        $request->session()->put('locale', $request->validated('locale'));

        return redirect()->to(
            AccountPages::destination($request, $request->validated('return_to'), includeAuthPages: true)
                ?? route($request->user() ? 'account.home' : 'account.login.show'),
            303
        );
    }
}
