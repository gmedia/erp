<?php

namespace App\Http\Requests\Employees;

use App\Http\Requests\AuthorizedFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Form request for syncing employee permissions.
 *
 * Handles validation for bulk permission assignment to an employee.
 */
class SyncPermissionsRequest extends AuthorizedFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
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
            'permissions.array' => __('form_validation.permissions_array'),
            'permissions.*.exists' => __('form_validation.permissions_exists'),
        ];
    }
}
