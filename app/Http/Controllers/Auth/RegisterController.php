<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CreateStudentAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Support\AccountPages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, CreateStudentAccount $create): JsonResponse|RedirectResponse
    {
        $user = $create->handle($request->validated());

        if (AccountPages::html($request)) {
            return redirect()->route('account.login.show', status: 303)->with('status', 'Your account is ready. Sign in to continue.');
        }

        return response()->json($user->only('id', 'name', 'email'), 201);
    }
}
