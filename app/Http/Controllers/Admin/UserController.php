<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Auth\AccountAccessTransaction;
use App\Actions\Auth\CreateStudentAccount;
use App\Actions\Auth\UpdateUserAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListUsersRequest;
use App\Http\Requests\Admin\SaveUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\AccountPages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(ListUsersRequest $request): JsonResponse|View
    {
        $search = $request->validated('search');
        $membership = $request->validated('membership');
        $users = User::query()
            ->select('id', 'name', 'email')
            ->with('roles:id,name')
            ->when($search !== null, fn (Builder $users) => $users->where(
                fn (Builder $matches) => $matches->whereLike('name', '%'.$search.'%')->orWhereLike('email', '%'.$search.'%')
            ))
            ->when($membership === 'assigned', fn (Builder $users) => $users->has('roles'))
            ->when($membership === 'unassigned', fn (Builder $users) => $users->doesntHave('roles'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->appends($request->safe()->only(['search', 'membership']));

        if (AccountPages::html($request)) {
            return AccountPages::view('A02', 'list', ['records' => $users, 'search' => $search, 'filter' => $membership]);
        }

        return response()->json($users->through(fn (User $user): array => $user->only('id', 'name', 'email') + [
            'roles' => $user->roles->map->only(['id', 'name']),
        ]));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return AccountPages::view('A03', 'user-editor', ['parent' => 'A02', 'record' => new User, 'assignable' => null]);
    }

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('update', $user);
        $user->load('roles');
        $assignable = null;
        if (Gate::allows('assignRoles', $user)) {
            $actor = $request->user();
            $granted = $actor->abilityQuery()->pluck('ability')->all();
            $administrator = $actor->isAdministrator();
            $assignable = Role::with('abilities')->orderBy('name')->get()->filter(
                fn (Role $role): bool => ($role->key !== Role::ADMINISTRATOR || $administrator)
                    && array_diff($role->abilities->pluck('ability')->all(), $granted) === []
            );
        }

        return AccountPages::view('A03', 'user-editor', ['parent' => 'A02', 'record' => $user, 'assignable' => $assignable]);
    }

    public function store(SaveUserRequest $request, AccountAccessTransaction $transaction, CreateStudentAccount $create): JsonResponse|RedirectResponse
    {
        $user = $transaction->run(function () use ($request, $create): User {
            Gate::forUser($request->user())->authorize('create', User::class);

            return $create->handle($request->validated());
        });

        if (AccountPages::html($request)) {
            return redirect()->route('admin.accounts.users.edit', $user, 303)->with('status', 'Account created.');
        }

        return response()->json($user->only('id', 'name', 'email'), 201);
    }

    public function update(SaveUserRequest $request, User $user, UpdateUserAccount $update): JsonResponse|RedirectResponse
    {
        $updated = $update->handle($request->user(), $user, $request->validated());

        if (AccountPages::html($request)) {
            return redirect()->route('admin.accounts.users.edit', $updated, 303)->with('status', 'Account updated.');
        }

        return response()->json($updated->only('id', 'name', 'email'));
    }
}
