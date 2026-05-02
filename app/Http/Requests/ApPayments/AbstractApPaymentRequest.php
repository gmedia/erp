<?php

namespace App\Http\Requests\ApPayments;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;

abstract class AbstractApPaymentRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'payment_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->paymentNumberUniqueRule(),
            ]),
            'supplier_id' => $this->withSometimes(['required', 'integer', 'exists:suppliers,id']),
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'fiscal_year_id' => $this->withSometimes(['required', 'integer', 'exists:fiscal_years,id']),
            'payment_date' => $this->withSometimes(['required', 'date']),
            'payment_method' => $this->withSometimes([
                'required',
                'string',
                'in:bank_transfer,cash,check,giro,other',
            ]),
            'bank_account_id' => $this->withSometimes(['required', 'integer', 'exists:accounts,id']),
            'currency' => $this->withSometimes(['required', 'string', 'max:3']),
            'total_amount' => $this->withSometimes(['required', 'numeric', 'gt:0']),
            'reference' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,pending_approval,confirmed,reconciled,cancelled,void',
            ]),
            'notes' => $this->withSometimes(['nullable', 'string']),

            'allocations' => $this->itemsRules(),
            'allocations.*.supplier_bill_id' => [$this->itemRequiredRule(), 'integer', 'exists:supplier_bills,id'],
            'allocations.*.allocated_amount' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'allocations.*.discount_taken' => ['nullable', 'numeric', 'min:0'],
            'allocations.*.notes' => ['nullable', 'string'],
        ];
    }

    abstract protected function paymentNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;
}
