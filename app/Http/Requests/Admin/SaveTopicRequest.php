<?php

namespace App\Http\Requests\Admin;

use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTopicRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->routeIs('admin.library.topics.relationships.update') && ! $this->expectsJson()) {
            foreach (array_keys(Topic::CONTENT) as $type) {
                $field = $type.'_ids';
                if ($this->has($field) && $this->input($field) === null) {
                    $this->merge([$field => []]);
                }
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('topic') ?? Topic::class);
    }

    public function rules(): array
    {
        $rules = $this->routeIs('admin.library.topics.relationships.update') ? [] : [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ];

        foreach (Topic::CONTENT as $type => $model) {
            $field = $type.'_ids';
            $rules[$field] = ['sometimes', 'array', 'list', 'max:100'];
            $rules[$field.'.*'] = ['bail', 'required', 'integer', 'min:1', 'distinct', Rule::exists($model, 'id')];
        }

        return $rules;
    }

    protected function getRedirectUrl(): string
    {
        $topic = $this->route('topic');

        return match ($this->route()->getName()) {
            'admin.library.topics.relationships.update' => route('admin.library.topics.relationships', $topic),
            'admin.library.topics.update' => route('admin.library.topics.edit', $topic),
            default => route('admin.library.topics.create'),
        };
    }
}
