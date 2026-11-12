<?php

namespace App\Actions\Curriculum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderCurriculum
{
    public function handle(Builder $siblings, int $id, int $position): void
    {
        DB::transaction(function () use ($siblings, $id, $position): void {
            $items = (clone $siblings)->ordered()->lockForUpdate()->get();
            $index = $items->search(fn ($item): bool => $item->getKey() === $id);

            abort_if($index === false, 404);

            if ($position > $items->count()) {
                throw ValidationException::withMessages(['position' => 'The position must be within the sibling list.']);
            }

            if ($index === $position - 1) {
                return;
            }

            $item = $items->splice($index, 1)->first();
            $items->splice($position - 1, 0, [$item]);

            $changed = $items->filter(fn ($sibling, int $index): bool => $sibling->position !== $index + 1);

            // Release the old positions before assigning the new ones to satisfy sibling uniqueness.
            (clone $siblings)->whereKey($changed->modelKeys())->update(['position' => null]);

            foreach ($changed as $index => $sibling) {
                $sibling->position = $index + 1;
                $sibling->save();
            }
        }, 3);
    }
}
