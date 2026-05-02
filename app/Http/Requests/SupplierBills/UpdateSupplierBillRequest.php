<?php

namespace App\Http\Requests\SupplierBills;

use Illuminate\Validation\Rule;

class UpdateSupplierBillRequest extends AbstractSupplierBillRequest
{
    protected function billNumberUniqueRule(): object
    {
        return Rule::unique('supplier_bills', 'bill_number')->ignore($this->route('supplierBill')?->id);
    }

    protected function usesSometimes(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function totalAmountRules(): array
    {
        return [
            'subtotal' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'grand_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'amount_paid' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'amount_due' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
