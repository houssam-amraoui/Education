<?php

namespace App\Http\Requests;

use App\Actions\Messaging\CreateConversation;
use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
{
    public function rules(): array
    {
        return CreateConversation::rules();
    }

    protected function getRedirectUrl(): string
    {
        return $this->fullUrl();
    }
}
