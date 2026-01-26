<?php

namespace App\Http\Requests\CustomerCategories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property \App\Models\CustomerCategory $customer_category
 *
 * @method \Illuminate\Routing\Route route($param = null)
 */
class UpdateCustomerCategoryRequest extends FormRequest
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
                Rule::unique('customer_categories', 'name')->ignore($this->route('customer_category')->id),
            ],
        ];
    }
}
