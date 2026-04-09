<?php

namespace App\Http\Requests\Customers;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesStringRules;
use Illuminate\Validation\Rule;

abstract class AbstractCustomerRequest extends AuthorizedFormRequest
{
    use HasSometimesStringRules;

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

    abstract protected function usesSometimes(): bool;

    /**
     * @return array<int, string|Rule>|string
     */
    private function emailRules(): array|string
    {
        if (! $this->usesSometimes()) {
            return 'required|email|unique:customers,email';
        }

        return [
            'sometimes',
            'required',
            'email',
            Rule::unique('customers', 'email')->ignore($this->route('customer')),
        ];
    }
}
