<?php

namespace App\Http\Requests\CustomerCategories;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:customer_categories,name',
        ];
    }
}
