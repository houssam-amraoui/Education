<?php

namespace App\Queries;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PointBalance
{
    public function transactions(User $student): Builder
    {
        return PointTransaction::query()->whereBelongsTo($student, 'student');
    }

    public function for(User $student): int
    {
        return (int) $this->transactions($student)->sum('amount');
    }
}
