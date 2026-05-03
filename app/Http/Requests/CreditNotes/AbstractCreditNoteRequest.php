<?php

namespace App\Http\Requests\CreditNotes;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;

abstract class AbstractCreditNoteRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'credit_note_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->creditNoteNumberUniqueRule(),
            ]),
            'customer_id' => $this->withSometimes(['required', 'integer', 'exists:customers,id']),
            'customer_invoice_id' => $this->withSometimes(['nullable', 'integer', 'exists:customer_invoices,id']),
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'fiscal_year_id' => $this->withSometimes(['required', 'integer', 'exists:fiscal_years,id']),
            'credit_note_date' => $this->withSometimes(['required', 'date']),
            'reason' => $this->withSometimes([
                'required',
                'string',
                'in:return,discount,correction,bad_debt,other',
            ]),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,confirmed,applied,cancelled,void',
            ]),
            'notes' => $this->withSometimes(['nullable', 'string']),
            ...$this->totalAmountRules(),

            'items' => $this->itemsRules(),
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.account_id' => [$this->itemRequiredRule(), 'integer', 'exists:accounts,id'],
            'items.*.description' => [$this->itemRequiredRule(), 'string', 'max:255'],
            'items.*.quantity' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.unit_price' => [$this->itemRequiredRule(), 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    abstract protected function creditNoteNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;

    protected function totalAmountRules(): array
    {
        return [];
    }
}
