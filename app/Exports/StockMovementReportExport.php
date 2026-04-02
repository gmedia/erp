<?php

namespace App\Exports;

use App\Actions\Reports\IndexStockMovementReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexStockMovementReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockMovementReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Product Code',
            'Product Name',
            'Category',
            'Warehouse Code',
            'Warehouse Name',
            'Branch',
            'Total In',
            'Total Out',
            'Ending Balance',
            'Last Movement',
        ];
    }

    public function map($row): array
    {
        return [
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            $row->category_name ?? '-',
            $row->warehouse_code ?? '-',
            $row->warehouse_name ?? '-',
            $row->branch_name ?? '-',
            $row->total_in ?? 0,
            $row->total_out ?? 0,
            $row->ending_balance ?? 0,
            $row->last_moved_at?->format('Y-m-d H:i:s') ?? '-',
        ];
    }

    protected function actionClass(): string
    {
        return IndexStockMovementReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexStockMovementReportRequest::class;
    }
}
