<?php

namespace App\Http\Requests\Branches;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property \App\Models\Branch $branch
 *
 * @method \Illuminate\Routing\Route route($param = null)
 */
class UpdateBranchRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'filled',
                'string',
                'max:255',
                Rule::unique('branches', 'name')->ignore($this->route('branch')->id),
            ],
        ];
    }
}
