<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateLearningPreferencesRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Support\AccountPages;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $student = $request->user();
        Gate::authorize('manageLearningProfile', $student);

        return AccountPages::view('S22', 'profile', ['parent' => 'S01', 'student' => $student]);
    }

    public function preferences(Request $request): View
    {
        $student = $request->user();
        Gate::authorize('manageLearningProfile', $student);

        return AccountPages::view('S23', 'profile', ['parent' => 'S22', 'student' => $student]);
    }

    public function update(UpdateProfileRequest $request): Response|RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));
        if ($request->safe()->has('city')) {
            $user->city = $request->validated('city');
        }
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        try {
            $user->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => 'email'])]);
        }

        if (AccountPages::html($request)) {
            return redirect()->route('account.profile.show', status: 303)->with('status', 'Profile saved.');
        }

        return response()->noContent();
    }

    public function updatePreferences(UpdateLearningPreferencesRequest $request): Response|RedirectResponse
    {
        $user = $request->user();
        $user->locale = $request->validated('locale');
        $user->daily_study_goal_minutes = $request->validated('daily_study_goal_minutes');
        $user->learning_focus = $request->validated('learning_focus');
        $user->save();

        if (AccountPages::html($request)) {
            $request->session()->put('locale', $user->locale);

            return redirect()->route('account.preferences.show', status: 303)->with('status', 'Settings saved.');
        }

        return response()->noContent();
    }
}
