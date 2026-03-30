<?php

namespace App\Http\Requests\GoodsReceipts;

use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractGoodsReceiptRequest extends FormRequest
{
    use HasSometimesArrayRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gr_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->grNumberUniqueRule(),
            ]),
            'purchase_order_id' => $this->withSometimes(['required', 'integer', 'exists:purchase_orders,id']),
            'warehouse_id' => $this->withSometimes(['required', 'integer', 'exists:warehouses,id']),
            'receipt_date' => $this->withSometimes(['required', 'date']),
            'supplier_delivery_note' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'status' => $this->withSometimes(['required', 'string', 'in:draft,confirmed,cancelled']),
            'notes' => $this->withSometimes(['nullable', 'string']),
            'received_by' => $this->withSometimes(['nullable', 'integer', 'exists:employees,id']),
            'confirmed_by' => $this->withSometimes(['nullable', 'integer', 'exists:users,id']),
            'confirmed_at' => $this->withSometimes(['nullable', 'date']),

            'items' => $this->itemsRules(),
            'items.*.purchase_order_item_id' => [$this->itemRequiredRule(), 'integer', 'exists:purchase_order_items,id'],
            'items.*.product_id' => [$this->itemRequiredRule(), 'integer', 'exists:products,id'],
            'items.*.unit_id' => [$this->itemRequiredRule(), 'integer', 'exists:units,id'],
            'items.*.quantity_received' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.quantity_accepted' => [$this->itemRequiredRule(), 'numeric', 'min:0'],
            'items.*.quantity_rejected' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => [$this->itemRequiredRule(), 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    abstract protected function grNumberUniqueRule(): string|object;

    abstract protected function usesSometimes(): bool;
}
