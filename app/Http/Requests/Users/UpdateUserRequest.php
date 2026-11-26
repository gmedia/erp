<?php

namespace App\Http\Requests\Users;

use App\Http\Requests\AuthorizedFormRequest;
use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Form request for creating or updating a user linked to an employee.
 *
 * Handles conditional validation based on whether a user already exists.
 */
class UpdateUserRequest extends AuthorizedFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');
        $existingUser = $employee->user;

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($existingUser?->id),
            ],
            'password' => $existingUser ? 'nullable|string|min:8' : 'required|string|min:8',
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
            'name.required' => __('validation.form_messages.user_name_required'),
            'email.required' => __('validation.form_messages.user_email_required'),
            'email.email' => __('validation.form_messages.user_email_email'),
            'email.unique' => __('validation.form_messages.user_email_unique'),
            'password.required' => __('validation.form_messages.user_password_required'),
            'password.min' => __('validation.form_messages.user_password_min'),
        ];
    }
}
