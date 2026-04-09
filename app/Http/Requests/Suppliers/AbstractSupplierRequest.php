<?php

namespace App\Http\Requests\Suppliers;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesStringRules;
use Illuminate\Validation\Rule;

abstract class AbstractSupplierRequest extends AuthorizedFormRequest
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
            'category_id' => $this->withSometimes('required|exists:supplier_categories,id'),
            'status' => $this->withSometimes('required|in:active,inactive'),
        ];
    }

    abstract protected function usesSometimes(): bool;

    /**
     * @return array<int, string|Rule>|string
     */
    private function emailRules(): array|string
    {
        if (! $this->usesSometimes()) {
            return 'nullable|email|unique:suppliers,email';
        }

        return [
            'nullable',
            'email',
            Rule::unique('suppliers', 'email')->ignore($this->route('supplier')->id),
        ];
    }
}
