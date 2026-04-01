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
            'ID' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->id,
            'PO Number' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->po_number,
            'Supplier' => fn (PurchaseOrder $purchaseOrder): mixed => $this->relatedAttribute($purchaseOrder, 'supplier', 'name'),
            'Warehouse' => fn (PurchaseOrder $purchaseOrder): mixed => $this->relatedAttribute($purchaseOrder, 'warehouse', 'name'),
            'Order Date' => fn (PurchaseOrder $purchaseOrder): mixed => $this->formatDateValue($purchaseOrder->order_date, 'Y-m-d'),
            'Expected Delivery Date' => fn (PurchaseOrder $purchaseOrder): mixed => $this->formatDateValue($purchaseOrder->expected_delivery_date, 'Y-m-d'),
            'Payment Terms' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->payment_terms,
            'Currency' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->currency,
            'Status' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->status,
            'Subtotal' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->subtotal,
            'Tax Amount' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->tax_amount,
            'Discount Amount' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->discount_amount,
            'Grand Total' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->grand_total,
            'Notes' => fn (PurchaseOrder $purchaseOrder): mixed => $purchaseOrder->notes,
            'Created At' => fn (PurchaseOrder $purchaseOrder): mixed => $this->formatIso8601($purchaseOrder->created_at),
        ];
    }
}
