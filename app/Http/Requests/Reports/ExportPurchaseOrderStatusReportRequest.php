<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

class ExportPurchaseOrderStatusReportRequest extends AbstractReportRequest
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
            'status_category' => [
                'nullable',
                'string',
                Rule::in(['outstanding', 'partially_received', 'closed']),
            ],
            ],
            $this->dateRangeRules(),
            $this->sortByEnumRules([
                'po_number',
                'purchase_order_po_number',
                'supplier_name',
                'warehouse_name',
                'order_date',
                'expected_delivery_date',
                'purchase_order_expected_delivery_date',
                'status',
                'purchase_order_status',
                'status_category',
                'purchase_order_status_category',
                'ordered_quantity',
                'received_quantity',
                'outstanding_quantity',
                'receipt_progress_percent',
                'grand_total',
            ]),
            $this->sortDirectionRules(),
            $this->exportFormatRules(),
        );
    }
}
