<?php

namespace App\Http\Requests\GoodsReceipts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gr_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('goods_receipts', 'gr_number')->ignore($this->route('goodsReceipt')?->id),
            ],
            'purchase_order_id' => ['sometimes', 'required', 'integer', 'exists:purchase_orders,id'],
            'warehouse_id' => ['sometimes', 'required', 'integer', 'exists:warehouses,id'],
            'receipt_date' => ['sometimes', 'required', 'date'],
            'supplier_delivery_note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,confirmed,cancelled'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'received_by' => ['sometimes', 'nullable', 'integer', 'exists:employees,id'],
            'confirmed_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'confirmed_at' => ['sometimes', 'nullable', 'date'],

            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required_with:items', 'integer', 'exists:purchase_order_items,id'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.unit_id' => ['required_with:items', 'integer', 'exists:units,id'],
            'items.*.quantity_received' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.quantity_accepted' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.quantity_rejected' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
