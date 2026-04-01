<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class PurchaseOrderExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = PurchaseOrder::query()->with(['supplier', 'warehouse']);

        $this->applyConfiguredFilters($query, $this->filters, ['po_number', 'payment_terms', 'notes', 'shipping_address'], [
            'supplier' => 'supplier_id',
            'warehouse' => 'warehouse_id',
            'status' => 'status',
            'currency' => 'currency',
        ], [
            'order_date' => ['from' => 'order_date_from', 'to' => 'order_date_to'],
        ], [
            'po_number',
            'order_date',
            'expected_delivery_date',
            'currency',
            'status',
            'grand_total',
            'created_at',
        ]);

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($purchaseOrder): array
    {
        return $this->mapExportRow($purchaseOrder, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->id,
            'PO Number' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->po_number,
            'Supplier' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->supplier?->name,
            'Warehouse' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->warehouse?->name,
            'Order Date' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->order_date?->format('Y-m-d'),
            'Expected Delivery Date' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->expected_delivery_date?->format('Y-m-d'),
            'Payment Terms' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->payment_terms,
            'Currency' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->currency,
            'Status' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->status,
            'Subtotal' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->subtotal,
            'Tax Amount' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->tax_amount,
            'Discount Amount' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->discount_amount,
            'Grand Total' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->grand_total,
            'Notes' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->notes,
            'Created At' => static fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->created_at?->toIso8601String(),
        ];
    }
}
