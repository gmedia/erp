<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

abstract class AbstractGoodsReceiptReportRequest extends AbstractReportRequest
{
    protected function goodsReceiptRules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
                'product_id' => ['nullable', 'integer', 'exists:products,id'],
                'status' => ['nullable', 'string', Rule::in(['draft', 'confirmed', 'cancelled'])],
            ],
            $this->dateRangeRules(),
            $this->sortByEnumRules([
                'gr_number',
                'goods_receipt_gr_number',
                'receipt_date',
                'goods_receipt_receipt_date',
                'status',
                'goods_receipt_status',
                'po_number',
                'purchase_order_po_number',
                'supplier_name',
                'warehouse_name',
                'item_count',
                'total_received_quantity',
                'total_accepted_quantity',
                'total_rejected_quantity',
                'total_receipt_value',
            ]),
            $this->sortDirectionRules(),
        );
    }
}
