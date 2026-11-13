<?php

namespace App\Http\Controllers\Admin;

use App\Models\Conversation;
use App\Models\Lesson;
use App\Support\AccountPages;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdministrationController
{
    public function __invoke(): View
    {
        $publishedLessons = Gate::allows('manage', Lesson::class) ? Lesson::publishedInCurriculum()->count() : null;
        $awaitingReply = null;
        $latestThread = null;

        if (Gate::allows('manage', Conversation::class)) {
            $unanswered = Conversation::whereHas('latestMessage', fn ($message) => $message->where('from_teacher', false));
            $awaitingReply = (clone $unanswered)->count();
            $latestThread = $unanswered->with('student:id,name')->latest('last_message_at')->latest('id')->first();
        }

        return AccountPages::view('A01', 'overview', compact('publishedLessons', 'awaitingReply', 'latestThread'), 'admin.overview');
    }
}
