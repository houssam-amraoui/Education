<?php

namespace App\Http\Controllers;

use App\Actions\Rewards\UnlockWithPoints;
use App\Http\Requests\ConfirmPointsUnlockRequest;
use App\Models\Level;
use App\Models\User;
use App\Policies\ContentAccess;
use App\Queries\AccessDestination;
use App\Queries\PointBalance;
use App\Support\AccountPages;
use App\Support\AccessTarget;
use App\Support\RewardTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AccessPageController
{
    public function __construct(private AccessDestination $access) {}

    public function show(Request $request, string $type, int $target): View|RedirectResponse
    {
        $record = $this->access->find($type, $target, $request->user());
        abort_unless($record || ($type === AccessTarget::FEATURE && $target === 0), 404);

        if ($this->access->allows($type, $target, $request->user(), $record)) {
            return redirect()->to($this->destination($request, $type, $record));
        }

        return $this->requirements($request, $type, $target, $record);
    }

    public function deniedPage(Request $request): ?Response
    {
        $type = match ($request->route()?->getName()) {
            'curriculum.level' => 'level',
            'curriculum.lesson' => 'lesson',
            'curriculum.exercises.instructions' => 'exercise',
            'curriculum.exams.instructions' => 'exam',
            'library.resources.show' => 'resource',
            'library.videos.show' => 'video',
            default => null,
        };
        if ($type === null || ! $request->isMethod('GET')) {
            return null;
        }

        $parameter = $request->route($type);
        $id = is_object($parameter) ? $parameter->getKey() : (int) $parameter;
        $record = $this->access->find($type, $id, $request->user());
        if ($record === null || url($request->getPathInfo()) !== AccessTarget::destination($type, $record)
            || $this->access->allows($type, $id, $request->user(), $record)) {
            return null;
        }

        return response($this->requirements($request, $type, $id, $record), 404);
    }

    public function unlock(Request $request, string $type, int $target, PointBalance $points, ContentAccess $policy): View
    {
        $student = $request->user();
        Gate::authorize('manageLearningProfile', $student);
        $record = $this->access->find($type, $target, $student);
        abort_unless($record || ($type === AccessTarget::FEATURE && $target === 0), 404);

        $destination = $this->destination($request, $type, $record);
        $canOpen = $this->access->allows($type, $target, $student, $record);
        $unlocked = $canOpen && $policy->hasPointsGrant($type, $target, $student);
        $cost = $canOpen ? null : $this->access->cost($type, $target, $student, $this->access->outstanding($type, $target, $student, $record));

        return AccountPages::view('S18', 'unlock', [
            'targetTitle' => $record?->title ?? 'Ask Teacher',
            'destination' => $destination,
            'back' => route('access.requirements', [$type, $target]),
            'submit' => route('access.unlock.store', [$type, $target]),
            'balance' => ($cost || $unlocked) ? $points->for($student) : null,
            'cost' => $cost,
            'canOpen' => $canOpen,
            'unlocked' => $unlocked,
            'parent' => 'S16',
        ], 'access.unlock');
    }

    public function spend(ConfirmPointsUnlockRequest $request, string $type, int $target, UnlockWithPoints $unlock): RedirectResponse
    {
        $student = $request->user();
        Gate::authorize('manageLearningProfile', $student);
        $record = $this->access->find($type, $target, $student);
        abort_unless($record || ($type === AccessTarget::FEATURE && $target === 0), 404);
        $return = route('access.unlock.show', [$type, $target]);

        if (! $this->access->allows($type, $target, $student, $record)) {
            $cost = $this->access->cost($type, $target, $student, $this->access->outstanding($type, $target, $student, $record));
            if ($cost === null) {
                return redirect()->to($return, 303)->withErrors(['points' => 'This points unlock is currently unavailable.']);
            }

            try {
                $unlock->handle($student, RewardTarget::from($type), $target, (int) $request->validated('points'));
            } catch (ValidationException $exception) {
                return redirect()->to($return, 303)->withErrors($exception->errors());
            } catch (ModelNotFoundException|NotFoundHttpException) {
                return redirect()->to($return, 303)->withErrors(['points' => 'This points unlock is currently unavailable.']);
            }
        }

        return redirect()->to($return, 303);
    }

    private function destination(Request $request, string $type, ?Model $record): string
    {
        $context = $type === AccessTarget::FEATURE ? $request->session()->get('access.ask_teacher_source') : null;

        return $context ? URL::signedRoute('account.messages.source', $context) : AccessTarget::destination($type, $record);
    }

    private function requirements(Request $request, string $type, int $id, ?Model $record): View
    {
        $viewer = $request->user();
        $isStudent = $viewer instanceof User && Gate::forUser($viewer)->allows('manageLearningProfile', $viewer);
        $rules = $this->access->outstanding($type, $id, $viewer, $record);
        $return = route('access.requirements', [$type, $id]);
        $cost = $isStudent ? $this->access->cost($type, $id, $viewer, $rules) : null;
        $prerequisites = $rules->where('requirement', 'prerequisite')->pluck('required_lesson_id')->filter()->unique()
            ->mapWithKeys(fn ($lesson) => [$lesson => $this->access->prerequisite($lesson, $viewer)]);
        $levels = Level::publishedInCurriculum()->whereIn('id', $rules->where('requirement', 'level')->pluck('required_level_id'))->pluck('title', 'id');

        return AccountPages::view('G23', 'access', [
            'targetTitle' => $record?->title ?? 'Ask Teacher',
            'rules' => $rules,
            'cost' => $cost,
            'isStudent' => $isStudent,
            'prerequisites' => $prerequisites,
            'levels' => $levels,
            'returnTo' => $return,
            'back' => $type === AccessTarget::FEATURE && ! $isStudent ? route('curriculum.home') : AccessTarget::catalogue($type),
            'unlockUrl' => $cost ? route('access.unlock.show', [$type, $id]) : null,
            'parent' => 'G01',
        ], 'access.requirements');
    }
}
