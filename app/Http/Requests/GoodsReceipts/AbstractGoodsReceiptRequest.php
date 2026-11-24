<?php

namespace App\Http\Requests\GoodsReceipts;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasRouteIgnoredUniqueRule;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use App\Models\PurchaseOrderItem;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

abstract class AbstractGoodsReceiptRequest extends AuthorizedFormRequest
{
    use HasRouteIgnoredUniqueRule;
    use HasSometimesArrayRules;

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $items = $this->input('items');

                if (! is_array($items)) {
                    return;
                }

                $existing = $this->route('goodsReceipt');
                $purchaseOrderId = $this->input('purchase_order_id', $existing?->purchase_order_id);

                if (! $purchaseOrderId) {
                    return;
                }

                foreach ($items as $index => $item) {
                    $lineId = $item['purchase_order_item_id'] ?? null;

                    if ($lineId === null || $lineId === '') {
                        continue;
                    }

                    $belongs = PurchaseOrderItem::query()
                        ->whereKey($lineId)
                        ->where('purchase_order_id', $purchaseOrderId)
                        ->exists();

                    if (! $belongs) {
                        $validator->errors()->add(
                            "items.{$index}.purchase_order_item_id",
                            __('goods_receipt.po_item_mismatch'),
                        );
                    }
                }
            },
        ];
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
            'items.*.purchase_order_item_id' => $this->requiredIntegerItemRule('exists:purchase_order_items,id'),
            'items.*.product_id' => $this->requiredIntegerItemRule('exists:products,id'),
            'items.*.unit_id' => $this->requiredIntegerItemRule('exists:units,id'),
            'items.*.quantity_received' => $this->requiredNumericItemRule('gt:0'),
            'items.*.quantity_accepted' => $this->requiredNumericItemRule('min:0'),
            'items.*.quantity_rejected' => $this->nullableNumericItemRule('min:0'),
            'items.*.unit_price' => $this->requiredNumericItemRule('min:0'),
            'items.*.notes' => $this->nullableStringItemRule(),
        ];
    }

    protected function grNumberUniqueRule(): Unique
    {
        return $this->routeIgnoredUniqueRule('goods_receipts', 'gr_number', 'goodsReceipt');
    }

    abstract protected function usesSometimes(): bool;
}
