<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pr_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('purchase_requests', 'pr_number')->ignore($this->route('purchaseRequest')?->id),
            ],
            'branch_id' => ['sometimes', 'required', 'integer', 'exists:branches,id'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'requested_by' => ['sometimes', 'nullable', 'integer', 'exists:employees,id'],
            'request_date' => ['sometimes', 'required', 'date'],
            'required_date' => ['sometimes', 'nullable', 'date'],
            'priority' => ['sometimes', 'required', 'string', 'in:low,normal,high,urgent'],
            'status' => [
                'sometimes',
                'required',
                'string',
                'in:draft,pending_approval,approved,rejected,partially_ordered,fully_ordered,cancelled',
            ],
            'estimated_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'approved_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['sometimes', 'nullable', 'date'],
            'rejection_reason' => ['sometimes', 'nullable', 'string'],

            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.unit_id' => ['required_with:items', 'integer', 'exists:units,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
