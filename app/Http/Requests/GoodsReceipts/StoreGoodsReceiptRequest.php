<?php

namespace App\Http\Requests\GoodsReceipts;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gr_number' => ['nullable', 'string', 'max:255', 'unique:goods_receipts,gr_number'],
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'receipt_date' => ['required', 'date'],
            'supplier_delivery_note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,confirmed,cancelled'],
            'notes' => ['nullable', 'string'],
            'received_by' => ['nullable', 'integer', 'exists:employees,id'],
            'confirmed_by' => ['nullable', 'integer', 'exists:users,id'],
            'confirmed_at' => ['nullable', 'date'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'gt:0'],
            'items.*.quantity_accepted' => ['required', 'numeric', 'min:0'],
            'items.*.quantity_rejected' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
