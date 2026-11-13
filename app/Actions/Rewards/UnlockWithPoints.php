<?php

namespace App\Actions\Rewards;

use App\Models\Entitlement;
use App\Models\PointCost;
use App\Models\PointTransaction;
use App\Models\PointUnlock;
use App\Models\User;
use App\Queries\PointBalance;
use App\Support\RewardTarget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UnlockWithPoints
{
    public function __construct(private LockPointAccount $lock, private PointBalance $balance) {}

    public function handle(User $student, RewardTarget $target, int $targetId, ?int $expectedPoints = null): PointUnlock
    {
        return DB::transaction(function () use ($student, $target, $targetId, $expectedPoints): PointUnlock {
            $this->lock->handle($student);
            Gate::forUser($student)->authorize('manageLearningProfile', $student);

            $cost = PointCost::query()->where('target_type', $target)->where('target_id', $targetId)
                ->lockForUpdate()->firstOrFail();

            $key = ['student_id' => $student->getKey(), 'point_cost_id' => $cost->getKey()];
            $existing = PointUnlock::where($key)->first();
            if ($existing !== null) {
                return $existing;
            }

            abort_unless($cost->enabled && $cost->points > 0 && $target->canUnlock($student, $targetId), 404);

            if ($expectedPoints !== null && $cost->points !== $expectedPoints) {
                throw ValidationException::withMessages(['points' => __('The points cost changed. Review the new cost before unlocking.')]);
            }

            if ($this->balance->for($student) < $cost->points) {
                throw ValidationException::withMessages(['points' => __('Insufficient point balance.')]);
            }

            $time = now()->utc();
            $debit = PointTransaction::create([
                'student_id' => $student->getKey(),
                'source_key' => "unlock:{$cost->id}",
                'amount' => -$cost->points,
                'recorded_at' => $time,
            ]);

            $unlock = PointUnlock::create($key + ['point_transaction_id' => $debit->id, 'unlocked_at' => $time]);
            Entitlement::create([
                'student_id' => $student->getKey(),
                'target_type' => $target->value,
                'target_id' => $targetId,
                'source' => 'points',
                'point_unlock_id' => $unlock->id,
                'granted_at' => $time,
            ]);

            return $unlock;
        }, 3);
    }
}
