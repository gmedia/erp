<?php

namespace App\Http\Requests\SupplierReturns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('supplier_returns', 'return_number')->ignore($this->route('supplierReturn')?->id),
            ],
            'purchase_order_id' => ['sometimes', 'required', 'integer', 'exists:purchase_orders,id'],
            'goods_receipt_id' => ['sometimes', 'nullable', 'integer', 'exists:goods_receipts,id'],
            'supplier_id' => ['sometimes', 'required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['sometimes', 'required', 'integer', 'exists:warehouses,id'],
            'return_date' => ['sometimes', 'required', 'date'],
            'reason' => ['sometimes', 'required', 'string', 'in:defective,wrong_item,excess_quantity,damaged,other'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,confirmed,cancelled'],
            'notes' => ['sometimes', 'nullable', 'string'],

            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.goods_receipt_item_id' => ['required_with:items', 'integer', 'exists:goods_receipt_items,id'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'items.*.quantity_returned' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
