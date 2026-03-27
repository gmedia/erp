<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

class IndexPurchaseHistoryReportRequest extends AbstractReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
                'product_id' => ['nullable', 'integer', 'exists:products,id'],
                'status' => [
                    'nullable',
                    'string',
                    Rule::in([
                        'draft',
                        'pending_approval',
                        'confirmed',
                        'rejected',
                        'partially_received',
                        'fully_received',
                        'cancelled',
                        'closed',
                    ]),
                ],
            ],
            $this->dateRangeRules(),
            $this->sortByEnumRules([
                'po_number',
                'purchase_order_po_number',
                'supplier_name',
                'product_name',
                'product_code',
                'warehouse_name',
                'order_date',
                'purchase_order_order_date',
                'expected_delivery_date',
                'purchase_order_expected_delivery_date',
                'status',
                'purchase_order_status',
                'ordered_quantity',
                'received_quantity',
                'outstanding_quantity',
                'receipt_count',
                'last_receipt_date',
                'goods_receipt_last_receipt_date',
                'total_purchase_value',
            ]),
            $this->sortDirectionRules(),
            $this->indexPaginationRules(),
        );
    }
}
