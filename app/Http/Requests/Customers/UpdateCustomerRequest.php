<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('customers', 'email')->ignore($this->route('customer')),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'category_id' => 'sometimes|required|exists:customer_categories,id',
            'status' => 'sometimes|required|in:active,inactive',
            'notes' => 'nullable|string',
        ];
    }
}
