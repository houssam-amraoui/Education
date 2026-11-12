<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveResourceLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('resource'));
    }

    public function rules(): array
    {
        return self::linkRules('present');
    }

    public static function linkRules(string $presence): array
    {
        return [
            'level_ids' => [$presence, 'array', 'list', 'max:100'],
            'level_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', Rule::exists('levels', 'id')],
            'lesson_ids' => [$presence, 'array', 'list', 'max:100'],
            'lesson_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', Rule::exists('lessons', 'id')],
        ];
    }
}
