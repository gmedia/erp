<?php

namespace App\Http\Requests\PurchaseOrders;

class StorePurchaseOrderRequest extends AbstractPurchaseOrderRequest
{
    protected function poNumberUniqueRule(): string
    {
        return 'unique:purchase_orders,po_number';
    }

    protected function usesSometimes(): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function expectedDeliveryDateRules(): array
    {
        return ['nullable', 'date', 'after_or_equal:order_date'];
    }
}
