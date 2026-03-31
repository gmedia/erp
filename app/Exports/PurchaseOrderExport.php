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

        $this->applySearchFilter($query, $this->filters, ['po_number', 'payment_terms', 'notes', 'shipping_address']);
        $this->applyExactFilters($query, $this->filters, [
            'supplier' => 'supplier_id',
            'warehouse' => 'warehouse_id',
            'status' => 'status',
            'currency' => 'currency',
        ]);
        $this->applyDateRangeFilters($query, $this->filters, [
            'order_date' => ['from' => 'order_date_from', 'to' => 'order_date_to'],
        ]);
        $this->applySorting($query, $this->filters, [
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
        return [
            'ID',
            'PO Number',
            'Supplier',
            'Warehouse',
            'Order Date',
            'Expected Delivery Date',
            'Payment Terms',
            'Currency',
            'Status',
            'Subtotal',
            'Tax Amount',
            'Discount Amount',
            'Grand Total',
            'Notes',
            'Created At',
        ];
    }

    public function map($purchaseOrder): array
    {
        return [
            $purchaseOrder->id,
            $purchaseOrder->po_number,
            $purchaseOrder->supplier?->name,
            $purchaseOrder->warehouse?->name,
            $purchaseOrder->order_date?->format('Y-m-d'),
            $purchaseOrder->expected_delivery_date?->format('Y-m-d'),
            $purchaseOrder->payment_terms,
            $purchaseOrder->currency,
            $purchaseOrder->status,
            $purchaseOrder->subtotal,
            $purchaseOrder->tax_amount,
            $purchaseOrder->discount_amount,
            $purchaseOrder->grand_total,
            $purchaseOrder->notes,
            $purchaseOrder->created_at?->toIso8601String(),
        ];
    }
}
