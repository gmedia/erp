<?php

namespace App\Http\Requests\SupplierBills;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;

abstract class AbstractSupplierBillRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'bill_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->billNumberUniqueRule(),
            ]),
            'supplier_id' => $this->withSometimes(['required', 'integer', 'exists:suppliers,id']),
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'fiscal_year_id' => $this->withSometimes(['required', 'integer', 'exists:fiscal_years,id']),
            'purchase_order_id' => $this->withSometimes(['nullable', 'integer', 'exists:purchase_orders,id']),
            'goods_receipt_id' => $this->withSometimes(['nullable', 'integer', 'exists:goods_receipts,id']),
            'supplier_invoice_number' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'supplier_invoice_date' => $this->withSometimes(['nullable', 'date']),
            'bill_date' => $this->withSometimes(['required', 'date']),
            'due_date' => $this->withSometimes($this->dueDateRules()),
            'payment_terms' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'currency' => $this->withSometimes(['required', 'string', 'max:3']),
            'status' => $this->withSometimes([
                'required',
                'string',
                'in:draft,confirmed,partially_paid,paid,overdue,cancelled,void',
            ]),
            'notes' => $this->withSometimes(['nullable', 'string']),
            ...$this->totalAmountRules(),

            'items' => $this->itemsRules(),
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.account_id' => [$this->itemRequiredRule(), 'integer', 'exists:accounts,id'],
            'items.*.description' => [$this->itemRequiredRule(), 'string', 'max:255'],
            'items.*.quantity' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.unit_price' => [$this->itemRequiredRule(), 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.goods_receipt_item_id' => ['nullable', 'integer', 'exists:goods_receipt_items,id'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    abstract protected function billNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;

    /**
     * @return array<string, array<int, string>>
     */
    protected function totalAmountRules(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function dueDateRules(): array
    {
        return ['required', 'date'];
    }
}
