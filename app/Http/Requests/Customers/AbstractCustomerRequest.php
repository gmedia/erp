<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractCustomerRequest extends FormRequest
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
            'name' => $this->withSometimes('required|string|max:255'),
            'email' => $this->emailRules(),
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'branch_id' => $this->withSometimes('required|exists:branches,id'),
            'category_id' => $this->withSometimes('required|exists:customer_categories,id'),
            'status' => $this->withSometimes('required|in:active,inactive'),
            'notes' => 'nullable|string',
        ];
    }

    /**
     * @return array<int, string|Rule>|string
     */
    private function emailRules(): array|string
    {
        if (! $this->isUpdateRequest()) {
            return 'required|email|unique:customers,email';
        }

        return [
            'sometimes',
            'required',
            'email',
            Rule::unique('customers', 'email')->ignore($this->route('customer')),
        ];
    }

    private function withSometimes(string $rules): string
    {
        if (! $this->isUpdateRequest()) {
            return $rules;
        }

        return 'sometimes|' . $rules;
    }

    private function isUpdateRequest(): bool
    {
        return $this instanceof UpdateCustomerRequest;
    }
}
