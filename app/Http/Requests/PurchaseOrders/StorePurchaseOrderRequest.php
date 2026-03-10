<?php

namespace App\Http\Requests\PurchaseOrders;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_number' => ['nullable', 'string', 'max:255', 'unique:purchase_orders,po_number'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:3'],
            'status' => ['required', 'string', 'in:draft,pending_approval,confirmed,rejected,partially_received,fully_received,cancelled,closed'],
            'notes' => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_request_item_id' => ['nullable', 'integer', 'exists:purchase_request_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
