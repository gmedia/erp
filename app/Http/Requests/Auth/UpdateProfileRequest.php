<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\AuthorizedFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the self-service profile update. Only the name is editable;
 * email is read-only here and changed by an administrator in the Users menu.
 */
class UpdateProfileRequest extends AuthorizedFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('profile.name_required'),
            'name.string' => __('profile.name_string'),
            'name.max' => __('profile.name_max'),
        ];
    }
}
