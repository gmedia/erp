<?php

namespace App\Http\Requests\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:suppliers'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category_id' => ['required', 'integer', 'exists:supplier_categories,id'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }
}
