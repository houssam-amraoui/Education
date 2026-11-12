<?php

namespace App\Http\Requests\Admin;

use App\Models\Exam;
use App\Models\Unit;
use App\Support\AccountPages;
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
        $html = AccountPages::html($this);
        $dateFormat = $html ? 'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s' : 'date_format:Y-m-d\TH:i:sP';
        $fileRules = $document
            ? [$exam ? 'sometimes' : 'bail', 'required', 'file', 'mimetypes:application/pdf', 'extensions:pdf', 'max:10240']
            : ['missing'];

        return [
            'type' => $exam ? ['sometimes', Rule::in([$exam->type])] : ['required', Rule::in(Exam::TYPES)],
            'unit_id' => ['required', 'integer', Rule::exists(Unit::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => [$interactive ? 'required' : 'nullable', 'string', 'max:10000'],
            'access' => ['sometimes', 'required', Rule::in(Exam::ACCESS)],
            'available_from' => ['nullable', $dateFormat],
            'available_until' => [
                'nullable', $dateFormat,
                Rule::when($this->filled('available_from'), 'after:available_from'),
            ],
            'exam_file' => $fileRules,
            'correction_file' => $fileRules,
            'timing_mode' => $interactive && $html ? ['required', Rule::in(['timed', 'untimed'])] : ['exclude'],
            'duration_minutes' => match (true) {
                ! $interactive => ['missing'],
                $html => ['exclude_if:timing_mode,untimed', 'required_if:timing_mode,timed', 'nullable', 'integer', 'min:1', 'max:65535'],
                default => ['present', 'nullable', 'integer', 'min:1', 'max:65535'],
            },
        ];
    }
}
