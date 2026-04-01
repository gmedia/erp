<?php

namespace App\Http\Requests\SupplierReturns;

use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractSupplierReturnMutationRequest extends FormRequest
{
    use HasSometimesArrayRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_number' => $this->withSometimes([
                'nullable',
                'string',
                'max:255',
                $this->returnNumberUniqueRule(),
            ]),
            'purchase_order_id' => $this->withSometimes(['required', 'integer', 'exists:purchase_orders,id']),
            'goods_receipt_id' => $this->withSometimes(['nullable', 'integer', 'exists:goods_receipts,id']),
            'supplier_id' => $this->withSometimes(['required', 'integer', 'exists:suppliers,id']),
            'warehouse_id' => $this->withSometimes(['required', 'integer', 'exists:warehouses,id']),
            'return_date' => $this->withSometimes(['required', 'date']),
            'reason' => $this->withSometimes(['required', 'string', 'in:defective,wrong_item,excess_quantity,damaged,other']),
            'status' => $this->withSometimes(['required', 'string', 'in:draft,confirmed,cancelled']),
            'notes' => $this->withSometimes(['nullable', 'string']),

            'items' => $this->itemsRules(),
            'items.*.goods_receipt_item_id' => [$this->itemRequiredRule(), 'integer', 'exists:goods_receipt_items,id'],
            'items.*.product_id' => [$this->itemRequiredRule(), 'integer', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'items.*.quantity_returned' => [$this->itemRequiredRule(), 'numeric', 'gt:0'],
            'items.*.unit_price' => [$this->itemRequiredRule(), 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    protected function returnNumberUniqueRule(): Rule|string
    {
        if (! $this->usesSometimes()) {
            return 'unique:supplier_returns,return_number';
        }

        return Rule::unique('supplier_returns', 'return_number')->ignore($this->route('supplierReturn')?->id);
    }

    abstract protected function usesSometimes(): bool;
}
