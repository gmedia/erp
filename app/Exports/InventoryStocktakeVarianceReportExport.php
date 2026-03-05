<?php

namespace App\Exports;

use App\Actions\Reports\IndexInventoryStocktakeVarianceReportAction;
use App\Http\Requests\Reports\IndexInventoryStocktakeVarianceReportRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryStocktakeVarianceReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true;
    }

    public function collection()
    {
        $action = app(IndexInventoryStocktakeVarianceReportAction::class);
        $request = new IndexInventoryStocktakeVarianceReportRequest();
        $request->merge($this->filters);

        return $action->execute($request);
    }

    public function headings(): array
    {
        return [
            'Stocktake Number',
            'Stocktake Date',
            'Product Code',
            'Product Name',
            'Category',
            'Warehouse',
            'Branch',
            'System Quantity',
            'Counted Quantity',
            'Variance',
            'Result',
            'Counted At',
            'Counted By',
        ];
    }

    public function map($row): array
    {
        return [
            $row->stocktake_number ?? '-',
            $row->stocktake_date?->format('Y-m-d') ?? '-',
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            $row->category_name ?? '-',
            $row->warehouse_name ?? '-',
            $row->branch_name ?? '-',
            $row->system_quantity ?? 0,
            $row->counted_quantity ?? 0,
            $row->variance ?? 0,
            $row->result ?? '-',
            $row->counted_at?->format('Y-m-d H:i:s') ?? '-',
            $row->counted_by_name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
