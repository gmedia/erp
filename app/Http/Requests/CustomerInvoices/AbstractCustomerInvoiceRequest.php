<?php

namespace App\Http\Requests\CustomerInvoices;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;

abstract class AbstractCustomerInvoiceRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'invoice_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->invoiceNumberUniqueRule(),
            ]),
            'customer_id' => $this->withSometimes(['required', 'integer', 'exists:customers,id']),
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'fiscal_year_id' => $this->withSometimes(['required', 'integer', 'exists:fiscal_years,id']),
            'invoice_date' => $this->withSometimes(['required', 'date']),
            'due_date' => $this->withSometimes(['required', 'date']),
            'payment_terms' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'currency' => $this->withSometimes(['required', 'string', 'max:3']),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,sent,partially_paid,paid,overdue,cancelled,void',
            ]),
            'notes' => $this->withSometimes(['nullable', 'string']),
            ...$this->totalAmountRules(),

            'items' => $this->itemsRules(),
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.account_id' => [$this->itemRequiredRule(), 'integer', 'exists:accounts,id'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'items.*.description' => [$this->itemRequiredRule(), 'string', 'max:255'],
            'items.*.quantity' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.unit_price' => [$this->itemRequiredRule(), 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    abstract protected function invoiceNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;

    protected function totalAmountRules(): array
    {
        return [];
    }
}
