<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderExport extends BaseExport
{
    public function query(): Builder
    {
        $query = PurchaseOrder::query()->with(['supplier', 'warehouse']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['po_number', 'payment_terms', 'notes', 'shipping_address'],
            [
                'supplier' => 'supplier_id',
                'warehouse' => 'warehouse_id',
                'status' => 'status',
                'currency' => 'currency',
            ],
            [
                'order_date' => ['from' => 'order_date_from', 'to' => 'order_date_to'],
            ],
            [
                'po_number',
                'order_date',
                'expected_delivery_date',
                'currency',
                'status',
                'grand_total',
                'created_at',
            ]
        );

        return $query;
    }

    protected function columns(): array
    {
        return [
            'ID' => fn (PurchaseOrder $po): mixed => $po->id,
            'PO Number' => fn (PurchaseOrder $po): mixed => $po->po_number,
            'Supplier' => fn (PurchaseOrder $po): mixed => $this->relatedAttribute($po, 'supplier', 'name'),
            'Warehouse' => fn (PurchaseOrder $po): mixed => $this->relatedAttribute($po, 'warehouse', 'name'),
            'Order Date' => fn (PurchaseOrder $po): mixed => $this->formatDateValue($po->order_date, 'Y-m-d'),
            'Expected Delivery Date' => fn (PurchaseOrder $po): mixed => $this->formatDateValue(
                $po->expected_delivery_date,
                'Y-m-d',
            ),
            'Payment Terms' => fn (PurchaseOrder $po): mixed => $po->payment_terms,
            'Currency' => fn (PurchaseOrder $po): mixed => $po->currency,
            'Status' => fn (PurchaseOrder $po): mixed => $po->status,
            'Subtotal' => fn (PurchaseOrder $po): mixed => $po->subtotal,
            'Tax Amount' => fn (PurchaseOrder $po): mixed => $po->tax_amount,
            'Discount Amount' => fn (PurchaseOrder $po): mixed => $po->discount_amount,
            'Grand Total' => fn (PurchaseOrder $po): mixed => $po->grand_total,
            'Notes' => fn (PurchaseOrder $po): mixed => $po->notes,
            'Created At' => fn (PurchaseOrder $po): mixed => $this->formatIso8601($po->created_at),
        ];
    }
}
