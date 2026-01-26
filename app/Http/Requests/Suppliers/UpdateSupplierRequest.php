<?php

namespace App\Http\Requests\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:suppliers,email,' . $this->route('supplier')->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'string'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:supplier_categories,id'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
