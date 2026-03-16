<?php

namespace App\Http\Requests\SupplierReturns;

use Illuminate\Foundation\Http\FormRequest;

class IndexSupplierReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'goods_receipt_id' => ['nullable', 'integer', 'exists:goods_receipts,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'reason' => ['nullable', 'string', 'in:defective,wrong_item,excess_quantity,damaged,other'],
            'status' => ['nullable', 'string', 'in:draft,confirmed,cancelled'],
            'return_date_from' => ['nullable', 'date'],
            'return_date_to' => ['nullable', 'date', 'after_or_equal:return_date_from'],
            'sort_by' => [
                'nullable',
                'string',
                'in:id,return_number,purchase_order,purchase_order_id,goods_receipt,goods_receipt_id,'
                    . 'supplier,supplier_id,warehouse,warehouse_id,return_date,reason,status,'
                    . 'created_at,updated_at',
            ],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
