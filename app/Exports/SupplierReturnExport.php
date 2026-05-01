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
        return $this->exportHeadings($this->columns());
    }

    public function map($supplierReturn): array
    {
        return $this->mapExportRow($supplierReturn, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (SupplierReturn $sr): mixed => $sr->id,
            'Return Number' => fn (SupplierReturn $sr): mixed => $sr->return_number,
            'PO Number' => fn (SupplierReturn $sr): mixed => $this->relatedAttribute($sr, 'purchaseOrder', 'po_number'),
            'GR Number' => fn (SupplierReturn $sr): mixed => $this->relatedAttribute($sr, 'goodsReceipt', 'gr_number'),
            'Supplier' => fn (SupplierReturn $sr): mixed => $this->relatedAttribute($sr, 'supplier', 'name'),
            'Warehouse' => fn (SupplierReturn $sr): mixed => $this->relatedAttribute($sr, 'warehouse', 'name'),
            'Return Date' => fn (SupplierReturn $sr): mixed => $this->formatDateValue($sr->return_date, 'Y-m-d'),
            'Reason' => fn (SupplierReturn $sr): mixed => $sr->reason,
            'Status' => fn (SupplierReturn $sr): mixed => $sr->status,
            'Notes' => fn (SupplierReturn $sr): mixed => $sr->notes,
            'Created At' => fn (SupplierReturn $sr): mixed => $this->formatIso8601($sr->created_at),
        ];
    }
}
