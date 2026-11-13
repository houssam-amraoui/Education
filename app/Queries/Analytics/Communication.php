<?php

namespace App\Queries\Analytics;

use App\Models\Conversation;
use App\Models\Message;
use App\Support\ReportingPeriod;
use Carbon\CarbonImmutable;

class Communication
{
    public function __construct(private AnalyticsActivity $activity) {}

    public function forPeriod(ReportingPeriod $period): array
    {
        $messages = $this->activity->during(new Message, 'created_at', $period);
        $teacherReplies = (clone $messages)->where('from_teacher', true);
        $firstReplies = (clone $teacherReplies)
            ->whereNotExists(Message::query()->from('messages as earlier')->selectRaw('1')
                ->whereColumn('earlier.conversation_id', 'messages.conversation_id')
                ->where('earlier.from_teacher', true)->whereColumn('earlier.id', '<', 'messages.id'))
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->select('messages.created_at as replied_at', 'conversations.created_at as requested_at');
        $responses = 0;
        $seconds = 0;

        foreach ($firstReplies->toBase()->cursor() as $reply) {
            $responses++;
            $seconds += CarbonImmutable::parse($reply->requested_at, 'UTC')
                ->diffInSeconds(CarbonImmutable::parse($reply->replied_at, 'UTC'));
        }

        return [
            'ask_teacher_requests' => $this->activity->during(new Conversation, 'created_at', $period)->count(),
            'messages' => $messages->count(),
            'daily_messages' => (clone $messages)->selectRaw('DATE(created_at) AS day, COUNT(*) AS total')
                ->groupByRaw('DATE(created_at)')->orderBy('day')->pluck('total', 'day')->map(fn ($total) => (int) $total)->all(),
            'awaiting_reply' => $this->activity->conversations($period)
                ->whereHas('latestMessage', fn ($message) => $message->where('from_teacher', false))
                ->count(),
            'student_messages' => (clone $messages)->where('from_teacher', false)->count(),
            'teacher_replies' => $teacherReplies->count(),
            'responding_teachers' => (clone $teacherReplies)->distinct()->count('sender_id'),
            'conversations_with_teacher_replies' => $teacherReplies->distinct()->count('conversation_id'),
            'first_teacher_replies' => $responses,
            'average_first_reply_seconds' => $responses === 0 ? null : (int) round($seconds / $responses),
        ];
    }
}
