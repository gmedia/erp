<?php

namespace App\Exports;

use App\Actions\Reports\IndexStockMovementReportAction;
use App\Http\Requests\Reports\IndexStockMovementReportRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true;
    }

    public function collection()
    {
        $action = app(IndexStockMovementReportAction::class);
        $request = new IndexStockMovementReportRequest;
        $request->merge($this->filters);

        return $action->execute($request);
    }

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

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
