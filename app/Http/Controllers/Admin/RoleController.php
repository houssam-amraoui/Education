<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Auth\SaveRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListRolesRequest;
use App\Http\Requests\Admin\SaveRoleRequest;
use App\Models\Role;
use App\Support\Ability;
use App\Support\AccountPages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(ListRolesRequest $request): JsonResponse|View
    {
        $roles = Role::with('abilities')->orderBy('name')->orderBy('id');
        if (AccountPages::html($request)) {
            $search = $request->validated('search');
            $type = $request->validated('type');
            $records = $roles
                ->when($search !== null, fn (Builder $roles) => $roles->where(
                    fn (Builder $matches) => $matches->whereLike('name', '%'.$search.'%')->orWhereLike('description', '%'.$search.'%')
                ))
                ->when($type === 'built-in', fn (Builder $roles) => $roles->whereNotNull('key'))
                ->when($type === 'custom', fn (Builder $roles) => $roles->whereNull('key'))
                ->paginate(20)
                ->appends($request->safe()->only(['search', 'type']));

            return AccountPages::view('A05', 'list', ['records' => $records, 'search' => $search, 'filter' => $type]);
        }

        return response()->json(['data' => $roles->get()->map($this->details(...))]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Role::class);

        return $this->editor($request, new Role);
    }

    public function edit(Request $request, Role $role): View
    {
        Gate::authorize('update', $role);

        return $this->editor($request, $role);
    }

    private function editor(Request $request, Role $role): View
    {
        $granted = $request->user()->abilityQuery()->pluck('ability')->all();
        $abilities = array_filter(Ability::cases(), fn (Ability $ability) => in_array($ability->value, $granted, true));

        return AccountPages::view('A06', 'role-editor', ['parent' => 'A05', 'record' => $role->load('abilities'), 'abilities' => $abilities]);
    }

    public function store(SaveRoleRequest $request, SaveRole $save): JsonResponse|RedirectResponse
    {
        $role = $save->handle($request->user(), $request->safe()->only(['name', 'description']), $request->validated('abilities'));

        if (AccountPages::html($request)) {
            return redirect()->route('admin.accounts.roles.index', status: 303)->with('status', 'Role created.');
        }

        return response()->json($this->details($role), 201);
    }

    public function update(SaveRoleRequest $request, Role $role, SaveRole $save): JsonResponse|RedirectResponse
    {
        $updated = $save->handle($request->user(), $request->safe()->only(['name', 'description']), $request->validated('abilities'), $role);

        if (AccountPages::html($request)) {
            return redirect()->route('admin.accounts.roles.index', status: 303)->with('status', 'Role updated.');
        }

        return response()->json($this->details($updated));
    }

    private function details(Role $role): array
    {
        return $role->only('id', 'key', 'name', 'description') + [
            'abilities' => $role->abilities->pluck('ability')->sort()->values(),
        ];
    }
}
