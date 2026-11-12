<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Auth\AssignUserRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignUserRolesRequest;
use App\Models\User;
use App\Support\AccountPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class UserRoleController extends Controller
{
    public function __invoke(AssignUserRolesRequest $request, User $user, AssignUserRoles $assign): Response|RedirectResponse
    {
        $assign->handle($request->user(), $user, $request->validated('role_ids'));

        if (AccountPages::html($request)) {
            return redirect()->route('admin.accounts.users.edit', $user, 303)->with('status', 'Roles updated.');
        }

        return response()->noContent();
    }
}
