<?php

namespace App\Http\Requests\PurchaseOrders;

use App\Http\Requests\BaseListingRequest;

class IndexPurchaseOrderRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
                'status' => [
                    'nullable',
                    'string',
                    'in:draft,pending_approval,confirmed,rejected,partially_received,fully_received,cancelled,closed',
                ],
                'currency' => ['nullable', 'string', 'max:3'],
                'order_date_from' => ['nullable', 'date'],
                'order_date_to' => ['nullable', 'date', 'after_or_equal:order_date_from'],
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
