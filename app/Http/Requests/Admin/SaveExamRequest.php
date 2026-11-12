<?php

namespace App\Http\Requests\Admin;

use App\Models\Exam;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('exam') ?? Exam::class);
    }

    public function rules(): array
    {
        $exam = $this->route('exam');
        $type = $exam?->type ?? $this->input('type');
        $document = $type === Exam::DOCUMENT;
        $interactive = $type === Exam::INTERACTIVE;
        $fileRules = $document
            ? [$exam ? 'sometimes' : 'bail', 'required', 'file', 'mimetypes:application/pdf', 'extensions:pdf', 'max:10240']
            : ['missing'];

        return [
            'type' => $exam ? ['sometimes', Rule::in([$exam->type])] : ['required', Rule::in([Exam::DOCUMENT, Exam::INTERACTIVE])],
            'unit_id' => ['required', 'integer', Rule::exists(Unit::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => [$interactive ? 'required' : 'nullable', 'string', 'max:10000'],
            'access' => ['sometimes', 'required', Rule::in(Exam::ACCESS)],
            'available_from' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
            'available_until' => [
                'nullable', 'date_format:Y-m-d\TH:i:sP',
                Rule::when($this->filled('available_from'), 'after:available_from'),
            ],
            'exam_file' => $fileRules,
            'correction_file' => $fileRules,
            'duration_minutes' => $interactive ? ['present', 'nullable', 'integer', 'min:1', 'max:65535'] : ['missing'],
        ];
    }
}
