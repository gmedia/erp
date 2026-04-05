<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\SupplierReturn;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class SupplierReturnExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = SupplierReturn::query()->with(['purchaseOrder', 'goodsReceipt', 'supplier', 'warehouse']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['return_number', 'notes'],
            [
                'purchase_order' => 'purchase_order_id',
                'goods_receipt' => 'goods_receipt_id',
                'supplier' => 'supplier_id',
                'warehouse' => 'warehouse_id',
                'reason' => 'reason',
                'status' => 'status',
            ],
            [
                'return_date' => ['from' => 'return_date_from', 'to' => 'return_date_to'],
            ],
            ['return_number', 'return_date', 'reason', 'status', 'created_at'],
        );

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Return Number',
            'PO Number',
            'GR Number',
            'Supplier',
            'Warehouse',
            'Return Date',
            'Reason',
            'Status',
            'Notes',
            'Created At',
        ];
    }

    public function map($supplierReturn): array
    {
        return [
            $supplierReturn->id,
            $supplierReturn->return_number,
            $supplierReturn->purchaseOrder?->po_number,
            $supplierReturn->goodsReceipt?->gr_number,
            $supplierReturn->supplier?->name,
            $supplierReturn->warehouse?->name,
            $supplierReturn->return_date?->format('Y-m-d'),
            $supplierReturn->reason,
            $supplierReturn->status,
            $supplierReturn->notes,
            $supplierReturn->created_at?->toIso8601String(),
        ];
    }
}
