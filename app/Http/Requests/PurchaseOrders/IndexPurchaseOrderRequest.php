<?php

namespace App\Http\Requests\PurchaseOrders;

use Illuminate\Foundation\Http\FormRequest;

class IndexPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'status' => ['nullable', 'string', 'in:draft,pending_approval,confirmed,rejected,partially_received,fully_received,cancelled,closed'],
            'currency' => ['nullable', 'string', 'max:3'],
            'order_date_from' => ['nullable', 'date'],
            'order_date_to' => ['nullable', 'date', 'after_or_equal:order_date_from'],
            'expected_delivery_date_from' => ['nullable', 'date'],
            'expected_delivery_date_to' => ['nullable', 'date', 'after_or_equal:expected_delivery_date_from'],
            'grand_total_min' => ['nullable', 'numeric', 'min:0'],
            'grand_total_max' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:id,po_number,supplier,supplier_id,warehouse,warehouse_id,order_date,expected_delivery_date,currency,status,grand_total,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
