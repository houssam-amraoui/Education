<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AccountPages
{
    private const DESTINATIONS = [
        'account.home', 'account.profile.show', 'account.preferences.show',
        'admin.accounts.users.index', 'admin.accounts.users.create',
        'admin.accounts.users.edit', 'admin.accounts.roles.index', 'admin.accounts.roles.create',
        'admin.accounts.roles.edit',
    ];

    public static function html(Request $request): bool
    {
        return $request->prefers(['application/json', 'text/html']) === 'text/html';
    }

    public static function destination(Request $request, mixed $url, bool $includeAuthPages = false): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $origin = $request->getSchemeAndHttpHost();
        if (str_starts_with($url, $origin.'/')) {
            $url = substr($url, strlen($origin));
        }

        if (! str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, chr(92)) || preg_match('/[\\x00-\\x20\\x7f]/', $url)) {
            return null;
        }

        try {
            $name = Route::getRoutes()->match(Request::create($url))->getName();
        } catch (HttpExceptionInterface) {
            return null;
        }

        $allowed = $includeAuthPages ? ['account.login.show', 'account.register.show', ...self::DESTINATIONS] : self::DESTINATIONS;

        return in_array($name, $allowed, true) ? $url : null;
    }

    public static function view(string $id, string $panel, array $data = []): View
    {
        $screens = Prototype::screens();
        $screen = $screens[$id];
        $actor = request()->user();
        $destinations = $actor
            ? ['S01' => route('account.home')]
            : ['G21' => route('account.register.show'), 'G22' => route('account.login.show')];
        if ($actor && Gate::allows('manageLearningProfile', $actor)) {
            $destinations['S22'] = route('account.profile.show');
            $destinations['S23'] = route('account.preferences.show');
        }
        if ($actor && Gate::allows('viewAny', User::class)) {
            $destinations['A02'] = route('admin.accounts.users.index');
        }
        if ($actor && Gate::allows('viewAny', Role::class)) {
            $destinations['A05'] = route('admin.accounts.roles.index');
        }

        $destinations['G01'] = $destinations['S01'] ?? $destinations['G22'];
        $destinations['A01'] = $destinations['A02'] ?? $destinations['A05'] ?? $destinations['G01'];
        $breadcrumbs = isset($data['parent']) ? [$screens[$data['parent']]] : [];
        $area = str_starts_with($id, 'A') ? 'admin' : ($actor ? 'student' : 'public');
        $url = fn (string $target): string => $destinations[$target];

        return view('account.page', $data + compact('screen', 'area', 'breadcrumbs', 'url', 'destinations', 'panel') + ['live' => true]);
    }
}
