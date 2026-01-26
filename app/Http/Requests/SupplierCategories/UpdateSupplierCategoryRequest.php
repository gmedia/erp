<?php

namespace App\Http\Requests\SupplierCategories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property \App\Models\SupplierCategory $supplier_category
 *
 * @method \Illuminate\Routing\Route route($param = null)
 */
class UpdateSupplierCategoryRequest extends FormRequest
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
                Rule::unique('supplier_categories', 'name')->ignore($this->route('supplier_category')->id),
            ],
        ];
    }
}
