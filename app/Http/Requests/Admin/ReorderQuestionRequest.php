<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return ['position' => ['required', 'integer', 'min:1']];
    }
}
