<?php

namespace App\Http\Requests\Auth;

class RegisterRequest extends AccountDetailsRequest
{
    public function rules(): array
    {
        return $this->detailsRules();
    }
}
