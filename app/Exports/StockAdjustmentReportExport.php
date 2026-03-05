<?php

namespace App\Exports;

use App\Actions\Reports\IndexStockAdjustmentReportAction;
use App\Http\Requests\Reports\IndexStockAdjustmentReportRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockAdjustmentReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true;
    }

    public function collection()
    {
        $action = app(IndexStockAdjustmentReportAction::class);
        $request = new IndexStockAdjustmentReportRequest();
        $request->merge($this->filters);

        return $action->execute($request);
    }

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

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
