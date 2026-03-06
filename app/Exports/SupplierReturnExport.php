<?php

namespace App\Exports;

use App\Models\SupplierReturn;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierReturnExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly array $filters = []
    ) {
    }

    public function query(): Builder
    {
        $query = SupplierReturn::query()->with(['purchaseOrder', 'goodsReceipt', 'supplier', 'warehouse']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['purchase_order'])) {
            $query->where('purchase_order_id', $this->filters['purchase_order']);
        }
        if (! empty($this->filters['goods_receipt'])) {
            $query->where('goods_receipt_id', $this->filters['goods_receipt']);
        }
        if (! empty($this->filters['supplier'])) {
            $query->where('supplier_id', $this->filters['supplier']);
        }
        if (! empty($this->filters['warehouse'])) {
            $query->where('warehouse_id', $this->filters['warehouse']);
        }
        if (! empty($this->filters['reason'])) {
            $query->where('reason', $this->filters['reason']);
        }
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (! empty($this->filters['return_date_from'])) {
            $query->whereDate('return_date', '>=', $this->filters['return_date_from']);
        }
        if (! empty($this->filters['return_date_to'])) {
            $query->whereDate('return_date', '<=', $this->filters['return_date_to']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = strtolower($this->filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['return_number', 'return_date', 'reason', 'status', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

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

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
