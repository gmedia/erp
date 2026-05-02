<?php

namespace App\Http\Requests\ArReceipts;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;

abstract class AbstractArReceiptRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'receipt_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->receiptNumberUniqueRule(),
            ]),
            'customer_id' => $this->withSometimes(['required', 'integer', 'exists:customers,id']),
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'fiscal_year_id' => $this->withSometimes(['required', 'integer', 'exists:fiscal_years,id']),
            'receipt_date' => $this->withSometimes(['required', 'date']),
            'payment_method' => $this->withSometimes([
                'required',
                'string',
                'in:bank_transfer,cash,check,giro,credit_card,other',
            ]),
            'bank_account_id' => $this->withSometimes(['required', 'integer', 'exists:accounts,id']),
            'currency' => $this->withSometimes(['required', 'string', 'max:3']),
            'reference' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,confirmed,reconciled,cancelled,void',
            ]),
            'notes' => $this->withSometimes(['nullable', 'string']),
            ...$this->totalAmountRules(),

            'allocations' => $this->itemsRules(),
            'allocations.*.customer_invoice_id' => [$this->itemRequiredRule(), 'integer', 'exists:customer_invoices,id'],
            'allocations.*.allocated_amount' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'allocations.*.discount_given' => ['nullable', 'numeric', 'min:0'],
            'allocations.*.notes' => ['nullable', 'string'],
        ];
    }

    abstract protected function receiptNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;

    protected function totalAmountRules(): array
    {
        return [];
    }
}
