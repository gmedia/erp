<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrderExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = PurchaseOrder::query()->with(['supplier', 'warehouse']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhere('payment_terms', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['supplier'])) {
            $query->where('supplier_id', $this->filters['supplier']);
        }
        if (! empty($this->filters['warehouse'])) {
            $query->where('warehouse_id', $this->filters['warehouse']);
        }
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (! empty($this->filters['currency'])) {
            $query->where('currency', $this->filters['currency']);
        }
        if (! empty($this->filters['order_date_from'])) {
            $query->whereDate('order_date', '>=', $this->filters['order_date_from']);
        }
        if (! empty($this->filters['order_date_to'])) {
            $query->whereDate('order_date', '<=', $this->filters['order_date_to']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = strtolower($this->filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, [
            'po_number',
            'order_date',
            'expected_delivery_date',
            'currency',
            'status',
            'grand_total',
            'created_at',
        ], true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

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

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
