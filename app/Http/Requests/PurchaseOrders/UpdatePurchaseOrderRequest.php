<?php

namespace App\Http\Requests\PurchaseOrders;

use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends AbstractPurchaseOrderRequest
{
    protected function poNumberUniqueRule(): object
    {
        return Rule::unique('purchase_orders', 'po_number')->ignore($this->route('purchaseOrder')?->id);
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
        ];
    }
}
