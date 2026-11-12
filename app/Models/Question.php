<?php

namespace App\Models;

use App\Models\Concerns\HasCurriculumOrder;
use App\Support\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasCurriculumOrder;

    protected $fillable = ['type', 'prompt', 'content', 'answer', 'points', 'explanation'];

    protected $hidden = ['answer', 'explanation', 'exam_type'];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'content' => 'array',
            'answer' => 'array',
            'points' => 'integer',
            'position' => 'integer',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
