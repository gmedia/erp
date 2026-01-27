<?php

namespace App\Http\Requests\Suppliers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
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
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('suppliers', 'email')->ignore($this->route('supplier')->id),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'category_id' => 'sometimes|required|exists:supplier_categories,id',
            'status' => 'sometimes|required|in:active,inactive',
        ];
    }
}
