<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversationFiltersRequest extends FormRequest
{
    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:120']];
    }

    protected function getRedirectUrl(): string
    {
        return $this->url();
    }
}
