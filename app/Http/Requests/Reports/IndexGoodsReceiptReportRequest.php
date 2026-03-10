<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexGoodsReceiptReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
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
            ],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'export' => ['nullable', 'boolean'],
        ];
    }
}
