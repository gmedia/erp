<?php

namespace App\Exports;

use App\Actions\Reports\IndexStockAdjustmentReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexStockAdjustmentReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockAdjustmentReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Adjustment Date',
            'Adjustment Type',
            'Status',
            'Warehouse Code',
            'Warehouse Name',
            'Branch',
            'Adjustment Count',
            'Total Quantity Adjusted',
            'Total Adjustment Value',
        ];
    }

    public function map($row): array
    {
        return [
            $row->adjustment_date?->format('Y-m-d') ?? '-',
            $row->adjustment_type ?? '-',
            $row->status ?? '-',
            $row->warehouse_code ?? '-',
            $row->warehouse_name ?? '-',
            $row->branch_name ?? '-',
            $row->adjustment_count ?? 0,
            $row->total_quantity_adjusted ?? 0,
            $row->total_adjustment_value ?? 0,
        ];
    }

    protected function actionClass(): string
    {
        return IndexStockAdjustmentReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexStockAdjustmentReportRequest::class;
    }
}
