<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\GoodsReceipt;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class GoodsReceiptExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = GoodsReceipt::query()->with(['purchaseOrder.supplier', 'warehouse', 'receiver']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['gr_number', 'supplier_delivery_note', 'notes'],
            [
                'purchase_order' => 'purchase_order_id',
                'warehouse' => 'warehouse_id',
                'status' => 'status',
                'received_by' => 'received_by',
            ],
            [
                'receipt_date' => ['from' => 'receipt_date_from', 'to' => 'receipt_date_to'],
            ],
            ['gr_number', 'receipt_date', 'status', 'created_at'],
        );

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
}
