<?php

namespace App\Http\Requests\Admin;

use App\Support\PlatformSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePlatformSettingsRequest extends FormRequest
{
    protected $redirectRoute = 'admin.settings.show';

    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return PlatformSettings::rules();
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->except(['settings', '_token', '_method']) !== []) {
                $validator->errors()->add('settings', __('Only supported platform settings may be submitted.'));
            }
        }];
    }
}
