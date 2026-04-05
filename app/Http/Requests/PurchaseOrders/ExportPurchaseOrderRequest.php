<?php

namespace App\Http\Requests\PurchaseOrders;

use App\Http\Requests\BaseListingRequest;

class ExportPurchaseOrderRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'supplier' => ['nullable', 'integer', 'exists:suppliers,id'],
                'warehouse' => ['nullable', 'integer', 'exists:warehouses,id'],
                'status' => [
                    'nullable',
                    'string',
                    'in:draft,pending_approval,confirmed,rejected,partially_received,fully_received,cancelled,closed',
                ],
                'currency' => ['nullable', 'string', 'max:3'],
                'order_date_from' => ['nullable', 'date'],
                'order_date_to' => ['nullable', 'date', 'after_or_equal:order_date_from'],
            ],
            $this->listingSortRules('po_number,order_date,expected_delivery_date,currency,status,grand_total,created_at'),
        );
    }
}
