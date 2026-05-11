<?php

namespace App\Http\Resources\SupplierBills;

use App\Http\Resources\Concerns\BuildsAuditStampResourceData;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SupplierBill $resource
 */
class SupplierBillResource extends JsonResource
{
    use BuildsAuditStampResourceData;

    public function toArray($request): array
    {
        /** @var Supplier|null $supplier */
        $supplier = $this->resource->supplier;
        /** @var Branch|null $branch */
        $branch = $this->resource->branch;
        /** @var FiscalYear|null $fiscalYear */
        $fiscalYear = $this->resource->fiscalYear;
        /** @var PurchaseOrder|null $purchaseOrder */
        $purchaseOrder = $this->resource->purchaseOrder;
        /** @var GoodsReceipt|null $goodsReceipt */
        $goodsReceipt = $this->resource->goodsReceipt;

        $items = [];

        if ($this->resource->relationLoaded('items')) {
            /** @var EloquentCollection<int, SupplierBillItem> $billItems */
            $billItems = $this->resource->items;

            $items = $billItems
                ->map(fn (SupplierBillItem $item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'account_id' => $item->account_id,
                    'account_name' => $item->account->name,
                    'description' => $item->description,
                    'quantity' => (string) $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                    'discount_percent' => (string) $item->discount_percent,
                    'tax_percent' => (string) $item->tax_percent,
                    'line_total' => (string) $item->line_total,
                    'goods_receipt_item_id' => $item->goods_receipt_item_id,
                    'notes' => $item->notes,
                ])
                ->values()
                ->all();
        }

        return [
            'id' => $this->resource->id,
            'bill_number' => $this->resource->bill_number,
            'supplier' => [
                'id' => $this->resource->supplier_id,
                'name' => $supplier?->name,
            ],
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $branch?->name,
            ],
            'fiscal_year' => [
                'id' => $this->resource->fiscal_year_id,
                'name' => $fiscalYear?->name,
            ],
            'purchase_order' => $this->resource->purchase_order_id ? [
                'id' => $this->resource->purchase_order_id,
                'po_number' => $purchaseOrder?->po_number,
            ] : null,
            'goods_receipt' => $this->resource->goods_receipt_id ? [
                'id' => $this->resource->goods_receipt_id,
                'gr_number' => $goodsReceipt?->gr_number,
            ] : null,
            'supplier_invoice_number' => $this->resource->supplier_invoice_number,
            'supplier_invoice_date' => $this->resource->supplier_invoice_date?->toDateString(),
            'bill_date' => $this->resource->bill_date->toDateString(),
            'due_date' => $this->resource->due_date->toDateString(),
            'payment_terms' => $this->resource->payment_terms,
            'currency' => $this->resource->currency,
            'subtotal' => (string) $this->resource->subtotal,
            'tax_amount' => (string) $this->resource->tax_amount,
            'discount_amount' => (string) $this->resource->discount_amount,
            'grand_total' => (string) $this->resource->grand_total,
            'amount_paid' => (string) $this->resource->amount_paid,
            'amount_due' => (string) $this->resource->amount_due,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'journal_entry_id' => $this->resource->journal_entry_id,
            ...$this->auditStampFields($this->resource),
            'items' => $items,
        ];
    }
}
