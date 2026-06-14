<?php

namespace App\Http\Requests\Concerns;

trait HasInvoiceLikeRules
{
    use HasSometimesArrayRules;
    use HasSupportedCurrencyRules;

    /**
     * Shared header rules for invoice-like documents (customer invoice, supplier bill).
     *
     * @return array<string, array<int, mixed>>
     */
    protected function invoiceLikeHeaderRules(): array
    {
        return [
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'fiscal_year_id' => $this->withSometimes(['required', 'integer', 'exists:fiscal_years,id']),
            'payment_terms' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'currency' => $this->withSometimes(['required', ...$this->supportedCurrencyRules()]),
            'notes' => $this->withSometimes(['nullable', 'string']),
        ];
    }

    /**
     * Shared item rules for invoice-like documents.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function invoiceLikeItemRules(): array
    {
        return [
            'items' => $this->itemsRules(),
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.account_id' => [$this->itemRequiredRule(), 'integer', 'exists:accounts,id'],
            'items.*.description' => [$this->itemRequiredRule(), 'string', 'max:255'],
            'items.*.quantity' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.unit_price' => [$this->itemRequiredRule(), 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
