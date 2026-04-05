<?php

namespace App\Http\Requests\PurchaseOrders;

class ExportPurchaseOrderRequest extends AbstractPurchaseOrderListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->purchaseOrderListingRules('supplier', 'warehouse'),
            $this->listingSortRules('po_number,order_date,expected_delivery_date,currency,status,grand_total,created_at'),
        );
    }
}
