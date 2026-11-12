<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Level;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Topic;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AccountPages
{
    private const DESTINATIONS = [
        'account.home', 'account.profile.show', 'account.preferences.show',
        'account.learning-goals.show', 'account.progress.show', 'account.progress.level', 'account.progress.history',
        'account.messages.index', 'account.messages.create', 'account.messages.source', 'account.messages.show',
        'account.rewards.index', 'account.rewards.show',
        'admin.rewards.points', 'admin.rewards.rules.create', 'admin.rewards.rules.edit',
        'admin.rewards.costs.create', 'admin.rewards.costs.edit', 'admin.rewards.streak.index', 'admin.rewards.streak.edit',
        'admin.rewards.achievements.index', 'admin.rewards.achievements.create', 'admin.rewards.achievements.edit',
        'admin.messages.index', 'admin.messages.show',
        'admin.accounts.users.index', 'admin.accounts.users.create',
        'admin.accounts.users.progress', 'admin.accounts.users.progress.review',
        'admin.accounts.users.edit', 'admin.accounts.roles.index', 'admin.accounts.roles.create',
        'admin.accounts.roles.edit',
        'curriculum.home', 'curriculum.paths', 'curriculum.level', 'curriculum.units',
        'curriculum.search', 'curriculum.topics.index', 'curriculum.topics.show',
        'curriculum.level.units', 'curriculum.unit', 'curriculum.category', 'curriculum.lesson',
        'curriculum.exercises.index', 'curriculum.exercises.instructions', 'curriculum.exercises.attempts.show',
        'curriculum.exercises.attempts.index',
        'curriculum.exams.index', 'curriculum.exams.instructions', 'curriculum.exams.document',
        'curriculum.exams.attempts.show', 'curriculum.exams.attempts.index',
        'admin.curriculum.levels.index', 'admin.curriculum.levels.create', 'admin.curriculum.levels.edit',
        'admin.curriculum.units.index', 'admin.curriculum.units.create', 'admin.curriculum.units.edit',
        'admin.curriculum.categories.index', 'admin.curriculum.categories.create', 'admin.curriculum.categories.edit',
        'admin.curriculum.lessons.index', 'admin.curriculum.lessons.create', 'admin.curriculum.lessons.edit',
        'admin.curriculum.lessons.relationships', 'admin.curriculum.lessons.preview',
        'admin.curriculum.exercises.index', 'admin.curriculum.exercises.create', 'admin.curriculum.exercises.edit',
        'admin.curriculum.exercises.questions.owners', 'admin.curriculum.exercises.questions.index',
        'admin.curriculum.exercises.questions.create', 'admin.curriculum.exercises.questions.edit',
        'admin.curriculum.exams.index', 'admin.curriculum.exams.create', 'admin.curriculum.exams.edit',
        'admin.curriculum.exams.questions.owners', 'admin.curriculum.exams.questions.index',
        'admin.curriculum.exams.questions.create', 'admin.curriculum.exams.questions.edit',
        'library.resources.index', 'library.resources.show', 'library.resources.document', 'library.lessons.resources.document',
        'admin.library.resources.index', 'admin.library.resources.create', 'admin.library.resources.edit',
        'library.videos.index', 'library.videos.show',
        'admin.library.topics.index', 'admin.library.topics.create', 'admin.library.topics.edit', 'admin.library.topics.relationships',
        'admin.library.videos.index', 'admin.library.videos.create', 'admin.library.videos.edit',
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

    public static function view(string $id, string $panel, array $data = [], string $view = 'account.page'): View
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
            $destinations['S02'] = route('account.learning-goals.show');
            $destinations['S10'] = route('account.progress.show');
            $destinations['S12'] = route('account.progress.history');
            $destinations['S16'] = route('account.rewards.index');
        }
        if ($actor && Gate::allows('rewards.manage')) {
            $destinations += [
                'A33' => route('admin.rewards.points'),
                'A34' => route('admin.rewards.rules.create'),
                'A35' => route('admin.rewards.achievements.index'),
                'A36' => route('admin.rewards.achievements.create'),
                'A37' => route('admin.rewards.streak.index'),
            ];
        }
        if ($actor && Gate::allows('viewAny', User::class)) {
            $destinations['A02'] = route('admin.accounts.users.index');
        }
        if ($actor && Gate::allows('create', Conversation::class)) {
            $destinations['S13'] = route('account.messages.index');
            $destinations['S14'] = route('account.messages.create');
        }
        if ($actor && Gate::allows('manage', Conversation::class)) {
            $destinations['A31'] = route('admin.messages.index');
        }
        if ($actor && Gate::allows('viewAny', Role::class)) {
            $destinations['A05'] = route('admin.accounts.roles.index');
        }

        if ($actor && Gate::allows('manage', Level::class)) {
            $destinations += [
                'A07' => route('admin.curriculum.levels.index'),
                'A09' => route('admin.curriculum.units.index'),
                'A11' => route('admin.curriculum.categories.index'),
                'A13' => route('admin.curriculum.lessons.index'),
                'A16' => route('admin.curriculum.exercises.index'),
                'A20' => route('admin.curriculum.exams.index'),
            ];
        }
        if ($actor && Gate::allows('manage', Resource::class)) {
            $destinations['A25'] = route('admin.library.resources.index');
        }
        if ($actor && Gate::allows('manage', Video::class)) {
            $destinations['A27'] = route('admin.library.videos.index');
        }

        if ($actor && Gate::allows('manage', Topic::class)) {
            $destinations['A29'] = route('admin.library.topics.index');
        }

        $destinations['G08'] = route('curriculum.search');
        $destinations['G09'] = route('curriculum.topics.index');
        $destinations['G01'] = route('curriculum.home');
        $destinations['G02'] = route('curriculum.paths');
        $destinations['G04'] = route('curriculum.units');
        $destinations['G11'] = route('curriculum.exercises.index');
        $destinations['G13'] = route('curriculum.exams.index');
        $destinations['G16'] = route('library.resources.index');
        $destinations['G19'] = route('library.videos.index');
        $destinations['A01'] = $destinations['A02'] ?? $destinations['A05'] ?? $destinations['A07'] ?? $destinations['G01'];
        $links = $data['links'] ?? [];
        $crumbs = $data['crumbs'] ?? (isset($data['parent']) ? [$data['parent']] : []);
        $breadcrumbs = array_map(fn (string $crumb): array => ($links[$crumb] ?? []) + $screens[$crumb], $crumbs);
        $screen['title'] = $data['title'] ?? $screen['title'];
        $area = str_starts_with($id, 'A') ? 'admin' : ($actor ? 'student' : 'public');
        $url = fn (string $target): string => $links[$target]['url'] ?? $destinations[$target];

        return view($view, $data + compact('screen', 'area', 'breadcrumbs', 'url', 'destinations', 'panel') + ['live' => true]);
    }
}
