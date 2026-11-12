<?php

namespace App\Http\Requests\Admin;

use App\Models\Video;
use App\Support\CurriculumStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListVideosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Video::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(CurriculumStatus::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.library.videos.index');
    }
}
