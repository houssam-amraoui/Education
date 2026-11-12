<?php

namespace App\Http\Requests\Admin;

use App\Support\QuestionDefinition;
use Illuminate\Foundation\Http\FormRequest;

class SaveQuestionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isJson()) {
            return;
        }

        $type = $this->input('type');
        if (! $this->has('content') && in_array($type, ['true_false', 'short_answer'], true)) {
            $this->merge(['content' => []]);
        }

        $answer = $this->input('answer');
        if ($type === 'fill_in_the_blanks' && is_array($answer['slots'] ?? null)) {
            foreach ($answer['slots'] as $index => $slot) {
                if (is_string($slot['accepted'] ?? null)) {
                    $answer['slots'][$index]['accepted'] = json_decode($slot['accepted'], true);
                }
            }

            $this->merge(['answer' => $answer]);
        }
    }

    public function rules(): array
    {
        return QuestionDefinition::rules($this->input('type'));
    }

    public function after(): array
    {
        return [QuestionDefinition::validateReferences(...)];
    }
}
