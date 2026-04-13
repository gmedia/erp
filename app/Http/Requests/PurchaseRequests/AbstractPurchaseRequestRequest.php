<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasRouteIgnoredUniqueRule;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractPurchaseRequestRequest extends AuthorizedFormRequest
{
    use HasRouteIgnoredUniqueRule;
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'pr_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->prNumberUniqueRule(),
            ]),
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'department_id' => $this->withSometimes(['nullable', 'integer', 'exists:departments,id']),
            'requested_by' => $this->withSometimes(['nullable', 'integer', 'exists:employees,id']),
            'request_date' => $this->withSometimes(['required', 'date']),
            'required_date' => $this->withSometimes($this->requiredDateRules()),
            'priority' => $this->withSometimes(['required', 'string', 'in:low,normal,high,urgent']),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,pending_approval,approved,rejected,partially_ordered,fully_ordered,cancelled',
            ]),
            'estimated_amount' => $this->withSometimes(['nullable', 'numeric', 'min:0']),
            'notes' => $this->withSometimes(['nullable', 'string']),
            'approved_by' => $this->withSometimes(['nullable', 'integer', 'exists:users,id']),
            'approved_at' => $this->withSometimes(['nullable', 'date']),
            'rejection_reason' => $this->withSometimes(['nullable', 'string']),

            'items' => $this->itemsRules(),
            'items.*.product_id' => [$this->itemRequiredRule(), 'integer', 'exists:products,id'],
            'items.*.unit_id' => [$this->itemRequiredRule(), 'integer', 'exists:units,id'],
            'items.*.quantity' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function requiredDateRules(): array
    {
        return ['nullable', 'date'];
    }

    protected function prNumberUniqueRule(): Unique
    {
        return $this->routeIgnoredUniqueRule('purchase_requests', 'pr_number', 'purchaseRequest');
    }

    abstract protected function usesSometimes(): bool;
}
