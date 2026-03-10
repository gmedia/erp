<?php

namespace App\Exports;

use App\Models\GoodsReceipt;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoodsReceiptExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly array $filters = []
    ) {
    }

    public function query(): Builder
    {
        $query = GoodsReceipt::query()->with(['purchaseOrder.supplier', 'warehouse', 'receiver']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('gr_number', 'like', "%{$search}%")
                    ->orWhere('supplier_delivery_note', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['purchase_order'])) {
            $query->where('purchase_order_id', $this->filters['purchase_order']);
        }
        if (! empty($this->filters['warehouse'])) {
            $query->where('warehouse_id', $this->filters['warehouse']);
        }
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (! empty($this->filters['received_by'])) {
            $query->where('received_by', $this->filters['received_by']);
        }
        if (! empty($this->filters['receipt_date_from'])) {
            $query->whereDate('receipt_date', '>=', $this->filters['receipt_date_from']);
        }
        if (! empty($this->filters['receipt_date_to'])) {
            $query->whereDate('receipt_date', '<=', $this->filters['receipt_date_to']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = strtolower($this->filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['gr_number', 'receipt_date', 'status', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'GR Number',
            'PO Number',
            'Supplier',
            'Warehouse',
            'Receipt Date',
            'Supplier Delivery Note',
            'Status',
            'Received By',
            'Notes',
            'Confirmed At',
            'Created At',
        ];
    }

    public function map($goodsReceipt): array
    {
        return [
            $goodsReceipt->id,
            $goodsReceipt->gr_number,
            $goodsReceipt->purchaseOrder?->po_number,
            $goodsReceipt->purchaseOrder?->supplier?->name,
            $goodsReceipt->warehouse?->name,
            $goodsReceipt->receipt_date?->format('Y-m-d'),
            $goodsReceipt->supplier_delivery_note,
            $goodsReceipt->status,
            $goodsReceipt->receiver?->name,
            $goodsReceipt->notes,
            $goodsReceipt->confirmed_at?->toIso8601String(),
            $goodsReceipt->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
