<?php

namespace App\Http\Requests\SupplierReturns;

use Illuminate\Foundation\Http\FormRequest;

class ExportSupplierReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'purchase_order' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'goods_receipt' => ['nullable', 'integer', 'exists:goods_receipts,id'],
            'supplier' => ['nullable', 'integer', 'exists:suppliers,id'],
            'warehouse' => ['nullable', 'integer', 'exists:warehouses,id'],
            'reason' => ['nullable', 'string', 'in:defective,wrong_item,excess_quantity,damaged,other'],
            'status' => ['nullable', 'string', 'in:draft,confirmed,cancelled'],
            'return_date_from' => ['nullable', 'date'],
            'return_date_to' => ['nullable', 'date', 'after_or_equal:return_date_from'],
            'sort_by' => ['nullable', 'string', 'in:return_number,return_date,reason,status,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
