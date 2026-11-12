<?php

namespace App\Http\Requests\Admin;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\Video;
use App\Support\AccountPages;
use App\Support\YouTubeUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveVideoRequest extends FormRequest
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
        return $this->user()->can('manage', $this->route('video') ?? Video::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'access' => ['sometimes', Rule::in(Video::ACCESS)],
            'source_url' => [
                ...($this->route('video') ? ['sometimes'] : []),
                'bail', 'required', 'string', 'max:2048',
                function (string $attribute, string $value, Closure $fail): void {
                    if (YouTubeUrl::videoId($value) === null) {
                        $fail('Enter a supported YouTube video URL.');
                    }
                },
            ],
            'level_ids' => ['sometimes', 'array', 'list', 'max:100'],
            'level_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', Rule::exists(Level::class, 'id')],
            'lesson_ids' => ['sometimes', 'array', 'list', 'max:100'],
            'lesson_ids.*' => ['bail', 'required', 'integer', 'min:1', 'distinct', Rule::exists(Lesson::class, 'id')],
        ];
    }

    protected function getRedirectUrl(): string
    {
        $video = $this->route('video');

        return $video ? route('admin.library.videos.edit', $video) : route('admin.library.videos.create');
    }
}
