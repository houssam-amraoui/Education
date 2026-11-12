<?php

namespace App\Models;

use App\Support\QuestionDefinition;
use App\Support\QuestionType;
use Illuminate\Database\Eloquent\Model;

class ExamAttemptItem extends Model
{
    public $timestamps = false;

    protected $hidden = ['answer_key', 'explanation'];

    public function learnerQuestion(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'prompt' => $this->prompt,
            'position' => $this->position,
            'points' => $this->points,
            'content' => QuestionDefinition::publicContent($this->type, $this->content),
        ];
    }

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'content' => 'array',
            'answer_key' => 'array',
            'submitted_answer' => 'json',
            'correct' => 'boolean',
            'points' => 'integer',
            'position' => 'integer',
            'awarded_points' => 'integer',
        ];
    }
}
