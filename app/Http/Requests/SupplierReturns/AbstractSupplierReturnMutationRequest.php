<?php

namespace App\Http\Requests\SupplierReturns;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class AbstractSupplierReturnMutationRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $touched = collect(['purchase_order_id', 'supplier_id', 'goods_receipt_id'])
                    ->contains(fn (string $key): bool => $this->has($key));

                if (! $touched) {
                    return;
                }

                $existing = $this->route('supplierReturn');

                $purchaseOrderId = $this->input('purchase_order_id', $existing?->purchase_order_id);
                $supplierId = $this->input('supplier_id', $existing?->supplier_id);
                $goodsReceiptId = $this->input('goods_receipt_id', $existing?->goods_receipt_id);

                if (! $purchaseOrderId) {
                    return;
                }

                $purchaseOrder = PurchaseOrder::query()->find($purchaseOrderId);

                if (! $purchaseOrder) {
                    return;
                }

                if ($supplierId && (int) $supplierId !== (int) $purchaseOrder->supplier_id) {
                    $validator->errors()->add('supplier_id', 'The supplier must match the purchase order supplier.');
                }

                if ($goodsReceiptId) {
                    $goodsReceipt = GoodsReceipt::query()->find($goodsReceiptId);

                    if ($goodsReceipt && (int) $goodsReceipt->purchase_order_id !== (int) $purchaseOrder->id) {
                        $validator->errors()->add('goods_receipt_id', 'The goods receipt must belong to the purchase order.');
                    }
                }
            },
        ];
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
            'reason' => $this->withSometimes([
                'required',
                'string',
                'in:defective,wrong_item,excess_quantity,damaged,other',
            ]),
            'status' => $this->withSometimes(['required', 'string', 'in:draft,confirmed,cancelled']),
            'notes' => $this->withSometimes(['nullable', 'string']),

            'items' => $this->itemsRules(),
            'items.*.goods_receipt_item_id' => $this->requiredIntegerItemRule('exists:goods_receipt_items,id'),
            'items.*.product_id' => $this->requiredIntegerItemRule('exists:products,id'),
            'items.*.unit_id' => $this->nullableIntegerItemRule('exists:units,id'),
            'items.*.quantity_returned' => $this->requiredNumericItemRule('gt:0'),
            'items.*.unit_price' => $this->requiredNumericItemRule('min:0'),
            'items.*.notes' => $this->nullableStringItemRule(),
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
