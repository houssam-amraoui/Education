<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SubmitExerciseAttemptRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('access', $this->route('attempt'));
    }

    public function rules(): array
    {
        return ['answers' => ['present', 'array']];
    }
}
