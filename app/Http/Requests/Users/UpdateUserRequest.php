<?php

namespace App\Http\Requests\Users;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for creating or updating a user linked to an employee.
 *
 * Handles conditional validation based on whether a user already exists.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already in use by another user.',
            'password.required' => 'A password is required when creating a new user.',
            'password.min' => 'The password must be at least 8 characters.',
        ];
    }
}
