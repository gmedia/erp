<?php

namespace App\Http\Requests\GoodsReceipts;

use Illuminate\Foundation\Http\FormRequest;

class IndexGoodsReceiptRequest extends FormRequest
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
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'status' => ['nullable', 'string', 'in:draft,confirmed,cancelled'],
            'received_by' => ['nullable', 'integer', 'exists:employees,id'],
            'receipt_date_from' => ['nullable', 'date'],
            'receipt_date_to' => ['nullable', 'date', 'after_or_equal:receipt_date_from'],
            'sort_by' => ['nullable', 'string', 'in:id,gr_number,purchase_order,purchase_order_id,warehouse,warehouse_id,receipt_date,status,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
