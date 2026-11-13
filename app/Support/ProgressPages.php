<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use stdClass;

class ProgressPages
{
    public static function lessonUrl(Lesson $lesson): string
    {
        $destination = LearningResult::from('lesson', $lesson)['destination'];

        return route($destination['route'], $destination['parameters']);
    }

    public static function activity(LengthAwarePaginator $history, User $student, bool $managed = false): LengthAwarePaginator
    {
        $events = $history->getCollection()->groupBy(fn (stdClass $row): string => str($row->event)->before('_')->toString());
        $items = [];
        foreach ($events as $type => $group) {
            $query = match ($type) {
                'lesson' => Lesson::visibleTo($student),
                'exercise' => Exercise::visibleTo($student),
                'exam' => Exam::visibleTo($student)->where('type', Exam::INTERACTIVE),
            };
            $items[$type] = $query->whereKey($group->pluck('content_id')->unique()->all())
                ->with(LearningResult::relations($type))->get()->keyBy('id');
        }

        return $history->through(function (stdClass $row) use ($items, $student, $managed): array {
            [$type, $status] = explode('_', $row->event, 2);
            $item = $items[$type]->get($row->content_id);
            $content = $item ? LearningResult::from($type, $item) : null;
            $url = null;
            if ($content && $type === 'lesson') {
                $url = route($content['destination']['route'], $content['destination']['parameters']);
            } elseif ($content && $status !== 'started') {
                $url = $managed
                    ? route('admin.accounts.users.progress.review', [$student, $type, $row->record_id])
                    : route('curriculum.'.$type.'s.attempts.show', [$row->content_id, $row->record_id]);
            }

            return [
                'title' => $content['title'] ?? 'Learning activity',
                'context' => implode(' · ', $content['context'] ?? []),
                'status' => ucfirst($status),
                'tone' => match ($status) {
                    'completed' => 'success', 'expired' => 'warning', default => 'info'
                },
                'date' => Carbon::parse($row->occurred_at, 'UTC'),
                'score' => $row->awarded_points === null ? '—' : $row->awarded_points.' / '.$row->possible_points,
                'url' => $url,
            ];
        });
    }
}
