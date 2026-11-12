<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    public const SOURCE_TYPES = [
        'lesson' => Lesson::class,
        'exercise' => Exercise::class,
        'exam' => Exam::class,
        'resource' => Resource::class,
        'video' => Video::class,
    ];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function latestTeacherReply(): HasOne
    {
        return $this->hasOne(Message::class)
            ->ofMany(['id' => 'max'], fn (Builder $messages) => $messages->where('from_teacher', true));
    }

    #[Scope]
    protected function ownedBy(Builder $query, User $student): void
    {
        $query->whereBelongsTo($student, 'student');
    }
}
