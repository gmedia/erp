<?php

namespace App\Http\Requests\PurchaseOrders;

class IndexPurchaseOrderRequest extends AbstractPurchaseOrderListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->purchaseOrderListingRules('supplier_id', 'warehouse_id'),
            [
                'expected_delivery_date_from' => ['nullable', 'date'],
                'expected_delivery_date_to' => ['nullable', 'date', 'after_or_equal:expected_delivery_date_from'],
                'grand_total_min' => ['nullable', 'numeric', 'min:0'],
                'grand_total_max' => ['nullable', 'numeric', 'min:0'],
            ],
            $this->listingSortRules(
                'id,po_number,supplier,supplier_id,warehouse,warehouse_id,order_date,expected_delivery_date,' .
                    'currency,status,grand_total,created_at,updated_at'
            ),
            $this->paginationRules(),
        );
    }
}
