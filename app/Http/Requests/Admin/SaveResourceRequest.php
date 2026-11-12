<?php

namespace App\Http\Requests\Admin;

use App\Models\Resource;
use App\Support\AccountPages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveResourceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (AccountPages::html($this)) {
            foreach (['level_ids', 'lesson_ids'] as $field) {
                if ($this->has($field) && $this->input($field) === null) {
                    $this->merge([$field => []]);
                }
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('resource') ?? Resource::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::in(Resource::TYPES)],
            'access' => ['sometimes', Rule::in(Resource::ACCESS)],
            'file' => ['sometimes', 'required', 'file', 'mimetypes:application/pdf', 'extensions:pdf', 'max:10240'],
            ...SaveResourceLinksRequest::linkRules('sometimes'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        $resource = $this->route('resource');

        return $resource ? route('admin.library.resources.edit', $resource) : route('admin.library.resources.create');
    }
}
