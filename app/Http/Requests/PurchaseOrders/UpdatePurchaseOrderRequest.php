<?php

namespace App\Http\Requests\PurchaseOrders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('purchase_orders', 'po_number')->ignore($this->route('purchaseOrder')?->id),
            ],
            'supplier_id' => ['sometimes', 'required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['sometimes', 'required', 'integer', 'exists:warehouses,id'],
            'order_date' => ['sometimes', 'required', 'date'],
            'expected_delivery_date' => ['sometimes', 'nullable', 'date'],
            'payment_terms' => ['sometimes', 'nullable', 'string', 'max:255'],
            'currency' => ['sometimes', 'required', 'string', 'max:3'],
            'subtotal' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'grand_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => [
                'sometimes',
                'required',
                'string',
                'in:draft,pending_approval,confirmed,rejected,partially_received,fully_received,cancelled,closed',
            ],
            'notes' => ['sometimes', 'nullable', 'string'],
            'shipping_address' => ['sometimes', 'nullable', 'string'],
            'approved_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['sometimes', 'nullable', 'date'],

            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.purchase_request_item_id' => ['nullable', 'integer', 'exists:purchase_request_items,id'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.unit_id' => ['required_with:items', 'integer', 'exists:units,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
