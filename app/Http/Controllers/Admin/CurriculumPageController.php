<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CurriculumFiltersRequest;
use App\Models\Category;
use App\Models\Level;
use App\Models\Unit;
use App\Support\AccountPages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CurriculumPageController extends Controller
{
    public function levels(CurriculumFiltersRequest $request): View
    {
        return $this->listing($request, 'A07', 'level', Level::withCount('units'), ['A01']);
    }

    public function units(CurriculumFiltersRequest $request): View
    {
        $parents = Level::ordered()->get();
        $level = $this->selectedParent($parents, $request->validated('level_id'));
        $query = Unit::withCount('categories')->where('level_id', $level?->id);

        return $this->listing($request, 'A09', 'unit', $query, ['A01', 'A07'], $parents, $level);
    }

    public function categories(CurriculumFiltersRequest $request): View
    {
        $parents = $this->unitChoices();
        $unit = $this->selectedParent($parents, $request->validated('unit_id'));
        $query = Category::where('unit_id', $unit?->id);

        return $this->listing($request, 'A11', 'category', $query, ['A01', 'A07', 'A09'], $parents, $unit);
    }

    public function createLevel(): View
    {
        return $this->editor('A08', 'level', new Level, ['A01', 'A07']);
    }

    public function editLevel(Level $level): View
    {
        return $this->editor('A08', 'level', $level, ['A01', 'A07']);
    }

    public function createUnit(CurriculumFiltersRequest $request): View
    {
        $unit = new Unit(['level_id' => $request->validated('level_id')]);

        return $this->editor('A10', 'unit', $unit, ['A01', 'A07', 'A09'], Level::ordered()->get());
    }

    public function editUnit(Unit $unit): View
    {
        Gate::authorize('manage', $unit);

        return $this->editor('A10', 'unit', $unit, ['A01', 'A07', 'A09'], Level::ordered()->get());
    }

    public function createCategory(CurriculumFiltersRequest $request): View
    {
        $category = new Category(['unit_id' => $request->validated('unit_id')]);

        return $this->editor('A12', 'category', $category, ['A01', 'A07', 'A09', 'A11'], $this->unitChoices());
    }

    public function editCategory(Category $category): View
    {
        Gate::authorize('manage', $category);

        return $this->editor('A12', 'category', $category, ['A01', 'A07', 'A09', 'A11'], $this->unitChoices());
    }

    private function listing(
        CurriculumFiltersRequest $request,
        string $id,
        string $kind,
        Builder $query,
        array $crumbs,
        ?Collection $parents = null,
        ?Model $parent = null,
    ): View {
        $query->ordered();
        $positions = (clone $query)->pluck($query->getModel()->getQualifiedKeyName())->flip();
        $search = $request->validated('search');
        $status = $request->validated('status');

        if ($search !== null) {
            $query->whereLike('title', '%'.$search.'%');
        }
        if ($status !== null) {
            $query->where('status', $status);
        }

        $context = $kind === 'unit' ? ['level_id' => $parent?->id] : ($kind === 'category' ? ['unit_id' => $parent?->id] : []);
        $records = $query->paginate(15)->appends($context + $request->validated());

        return AccountPages::view($id, 'list', compact(
            'kind', 'records', 'positions', 'search', 'status', 'crumbs', 'parents', 'parent', 'context',
        ) + ['links' => $this->links($parent)], 'curriculum.admin-list');
    }

    private function editor(string $id, string $kind, Model $record, array $crumbs, ?Collection $parents = null): View
    {
        if ($kind === 'level') {
            Gate::authorize('manage', $record);
        }
        $parent = match ($kind) {
            'unit' => $parents->find($record->level_id),
            'category' => $parents->find($record->unit_id),
            default => null,
        };
        $context = $kind === 'unit' ? ['level_id' => $parent?->id] : ($kind === 'category' ? ['unit_id' => $parent?->id] : []);
        $links = $this->links($parent);
        if ($record instanceof Unit && $record->exists) {
            $links['A11'] = ['url' => route('admin.curriculum.categories.index', ['unit_id' => $record->id])];
        }

        return AccountPages::view($id, 'editor', compact(
            'kind', 'record', 'crumbs', 'parents', 'parent', 'context', 'links',
        ), 'curriculum.admin-editor');
    }

    private function unitChoices(): Collection
    {
        return Unit::with('level')->orderBy('level_id')->ordered()->get();
    }

    private function selectedParent(Collection $parents, mixed $id): ?Model
    {
        return $id === null ? $parents->first() : $parents->findOrFail($id);
    }

    private function links(?Model $parent): array
    {
        $level = $parent instanceof Unit ? $parent->level : $parent;
        $links = [];

        if ($level) {
            $links['A09'] = ['url' => route('admin.curriculum.units.index', ['level_id' => $level->id])];
        }
        if ($parent instanceof Unit) {
            $links['A11'] = ['url' => route('admin.curriculum.categories.index', ['unit_id' => $parent->id])];
        }

        return $links;
    }
}
